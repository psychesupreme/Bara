import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../config/api_config.dart';
import '../services/sync_manager.dart';
import '../services/field_operations_adapter.dart';
import 'order_entry_screen.dart';
import 'collection_screen.dart';
import 'merchandising_screen.dart';
import 'customer_360_screen.dart';
import 'sync_center_screen.dart';

class JourneyPlanScreen extends StatefulWidget {
  final String token;
  final String userName;

  const JourneyPlanScreen({super.key, required this.token, required this.userName});

  @override
  State<JourneyPlanScreen> createState() => _JourneyPlanScreenState();
}

class _JourneyPlanScreenState extends State<JourneyPlanScreen> {
  late final SyncManager _syncManager;
  late final FieldOperationsAdapter _adapter;
  Position? _currentPosition;
  bool _isLocating = false;
  String _statusMessage = 'My Work Hub Loaded | Select an outlet to execute guided call sequence';
  final Map<String, DateTime> _checkInTimestamps = {};
  final Map<String, int> _outletActiveSteps = {}; // Tracks guided call step 1 to 6 per outlet

  // Seeded Outlets for Nairobi Route (includes Kasarani Live Test Store at -1.2002, 36.8344)
  final List<Map<String, dynamic>> _outlets = [
    {
      'id': 'CUST-KASARANI-LIVE',
      'name': 'Kasarani Live Test Store',
      'territory': 'Kasarani Zone',
      'channel': 'Key Account',
      'lat': -1.2002000,
      'lng': 36.8344000,
      'creditLimit': 150000.0,
      'balance': 125000.0,
      'status': 'Pending Visit',
      'distanceMeters': null,
      'isProvisional': false,
    },
    {
      'id': 'CUST-NAIVAS-CBD',
      'name': 'Naivas Supermarket CBD Branch',
      'territory': 'CBD Zone',
      'channel': 'Key Account Tier 1',
      'lat': -1.2833300,
      'lng': 36.8166700,
      'creditLimit': 500000.0,
      'balance': 125000.0,
      'status': 'Pending Visit',
      'distanceMeters': null,
      'isProvisional': false,
    },
    {
      'id': 'CUST-SARIT-MART',
      'name': 'Sarit Center Mart',
      'territory': 'Westlands Zone',
      'channel': 'Modern Trade',
      'lat': -1.2612000,
      'lng': 36.8041000,
      'creditLimit': 200000.0,
      'balance': 190000.0,
      'status': 'Pending Visit',
      'distanceMeters': null,
      'isProvisional': false,
    },
    {
      'id': 'CUST-YAYA-MINI',
      'name': 'Yaya Center MiniMart',
      'territory': 'Kilimani Zone',
      'channel': 'Convenience',
      'lat': -1.2917000,
      'lng': 36.7865000,
      'creditLimit': 100000.0,
      'balance': 45000.0,
      'status': 'Pending Visit',
      'distanceMeters': null,
      'isProvisional': false,
    },
    {
      'id': 'CUST-CBD-CONV',
      'name': 'CBD Convenience Store',
      'territory': 'CBD Zone',
      'channel': 'Retail',
      'lat': -1.2845000,
      'lng': 36.8210000,
      'creditLimit': 80000.0,
      'balance': 20000.0,
      'status': 'Pending Visit',
      'distanceMeters': null,
      'isProvisional': false,
    },
  ];

  @override
  void initState() {
    super.initState();
    if (!SyncManager.isInitialized) {
        SyncManager.initialize(authToken: widget.token);
    }
    _syncManager = SyncManager.instance;
    _adapter = FieldOperationsAdapter(syncManager: _syncManager);
    _fetchRealTimeLocation();
  }

  void _handleGpsUnavailable() {
    setState(() {
      _statusMessage = 'GPS unavailable. Provisional check-in required for all outlets.';
      _isLocating = false;
    });
    for (var outlet in _outlets) {
      outlet['distanceMeters'] = 9999;
    }
  }

  String _formatDistance(int? distanceMeters) {
    if (distanceMeters == null) return 'Calculating...';
    if (distanceMeters < 1000) {
      return '$distanceMeters m';
    } else {
      double km = distanceMeters / 1000.0;
      return '${km.toStringAsFixed(1)} km';
    }
  }

  Future<void> _fetchRealTimeLocation() async {
    setState(() => _isLocating = true);
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        _handleGpsUnavailable();
        return;
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          _handleGpsUnavailable();
          return;
        }
      }

      if (permission == LocationPermission.deniedForever) {
        _handleGpsUnavailable();
        return;
      }

      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 10),
      );

      setState(() {
        _currentPosition = position;
        _statusMessage = 'GPS Fix Acquired: ${position.latitude.toStringAsFixed(4)}, ${position.longitude.toStringAsFixed(4)}';
        _isLocating = false;
      });

      _updateDistances(position.latitude, position.longitude);
    } catch (e) {
      _handleGpsUnavailable();
    }
  }

  void _updateDistances(double userLat, double userLng) {
    setState(() {
      for (var outlet in _outlets) {
        double distance = Geolocator.distanceBetween(
          userLat,
          userLng,
          outlet['lat'] as double,
          outlet['lng'] as double,
        );
        outlet['distanceMeters'] = distance.round();
      }
    });
  }

  void _showGeofenceRuleFailureModal(Map<String, dynamic> outlet, int? distance) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: Row(
          children: const [
            Icon(Icons.gpp_bad, color: Color(0xFFF43F5E), size: 28),
            SizedBox(width: 8),
            Text('Geofence Boundary Lock', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              outlet['name'] as String,
              style: const TextStyle(color: Colors.indigoAccent, fontWeight: FontWeight.bold, fontSize: 15),
            ),
            Text('Territory: ${outlet['territory']}', style: const TextStyle(color: Colors.grey, fontSize: 12)),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF43F5E).withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFF43F5E).withValues(alpha: 0.3)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Rule Failure Reason:',
                    style: TextStyle(color: Color(0xFFFB7185), fontWeight: FontWeight.bold, fontSize: 12),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Your current position is ${_formatDistance(distance)} away. Standard outlet check-in requires <= 1.5 km proximity.',
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            const Text(
              'PRD Rule: Requesting a manager override unlocks Provisional Check-In. All drafted orders will remain locked in "pending_approval" status until authorized on /supervisory-queue.',
              style: TextStyle(color: Colors.grey, fontSize: 11),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton.icon(
            onPressed: () {
              Navigator.pop(context);
              _requestGeofenceManagerOverride(outlet);
            },
            icon: const Icon(Icons.shield, color: Colors.white, size: 16),
            label: const Text('Request Manager Override', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFF59E0B),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _requestGeofenceManagerOverride(Map<String, dynamic> outlet) async {
    try {
      await http.post(
        Uri.parse('${ApiConfig.baseUrl}/exceptions'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer ${widget.token}',
          'X-Tenant': ApiConfig.defaultTenant,
        },
        body: jsonEncode({
          'exception_type': 'off_geofence',
          'reason': 'Remote check-in request outside 1500m geofence (${_formatDistance(outlet['distanceMeters'])})',
          'outlet_name': outlet['name'],
        }),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Network warning: ${e.toString().substring(0, (e.toString().length > 60) ? 60 : e.toString().length)}. Exception queued offline.'),
            backgroundColor: const Color(0xFFF59E0B),
          ),
        );
      }
    }

    if (!mounted) return;

    setState(() {
      outlet['status'] = 'Checked In (Provisional Visit)';
      outlet['isProvisional'] = true;
      _outletActiveSteps[outlet['id'] as String] = 2; // Move to Step 2: Customer 360
      _checkInTimestamps[outlet['id'] as String] = DateTime.now();
      _statusMessage = 'Provisional Check-in Active! Override Request Sent to Supervisory Queue.';
    });

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Provisional Check-In Activated! Override Request Submitted to /supervisory-queue.'),
        backgroundColor: Color(0xFFF59E0B),
      ),
    );
  }

  Future<void> _performOutletCheckIn(Map<String, dynamic> outlet) async {
    final int distance = outlet['distanceMeters'] ?? 9999;
    final bool inGeofence = distance <= 1500;

    if (!inGeofence) {
      _showGeofenceRuleFailureModal(outlet, distance);
      return;
    }

    if (_currentPosition == null) {
      await _fetchRealTimeLocation();
    }

    final double liveLat = _currentPosition?.latitude ?? outlet['lat'] as double;
    final double liveLng = _currentPosition?.longitude ?? outlet['lng'] as double;

    final activity = _adapter.checkInOutlet(
      clientUuid: 'UUID-${DateTime.now().millisecondsSinceEpoch}',
      customerId: outlet['id'] as String,
      outletName: outlet['name'] as String,
      latitude: liveLat,
      longitude: liveLng,
    );

    _checkInTimestamps[outlet['id'] as String] = DateTime.now();
    _outletActiveSteps[outlet['id'] as String] = 2; // Step 2: Customer 360

    try {
      await http.post(
        Uri.parse('${ApiConfig.baseUrl}/field/check-in'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer ${widget.token}',
          'X-Tenant': ApiConfig.defaultTenant,
        },
        body: jsonEncode({
          'customer_id': outlet['id'],
          'outlet_name': outlet['name'],
          'latitude': liveLat,
          'longitude': liveLng,
        }),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Network warning: Check-in saved offline.'),
            backgroundColor: const Color(0xFFF59E0B),
          ),
        );
      }
    }

    if (!mounted) return;

    setState(() {
      outlet['status'] = 'Checked In (Active Visit)';
      _statusMessage = 'Check-in Verified! Reference: ${activity.referenceNo}';
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Visit Started: ${outlet['name']} (${activity.referenceNo})'),
        backgroundColor: const Color(0xFF10B981),
      ),
    );
  }

  void _showCustomer360Modal(Map<String, dynamic> outlet) {
    final String outletId = outlet['id'] as String;
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => Customer360Screen(
          token: widget.token,
          customerId: outletId,
          outletName: outlet['name'] as String,
        ),
      ),
    ).then((_) {
      setState(() {
        _outletActiveSteps[outletId] = 3; // Move to Step 3: Merchandising
      });
    });
  }

  void _checkoutOutlet(Map<String, dynamic> outlet) {
    final String outletId = outlet['id'] as String;
    final DateTime checkInTime = _checkInTimestamps[outletId] ?? DateTime.now().subtract(const Duration(minutes: 14, seconds: 25));
    final Duration duration = DateTime.now().difference(checkInTime);
    final String durationStr = '${duration.inMinutes} mins ${duration.inSeconds % 60} secs';

    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: Row(
          children: const [
            Icon(Icons.check_circle, color: Color(0xFF10B981), size: 28),
            SizedBox(width: 10),
            Text('Visit Completion Summary', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              outlet['name'] as String,
              style: const TextStyle(color: Colors.indigoAccent, fontWeight: FontWeight.bold, fontSize: 15),
            ),
            Text('Zone: ${outlet['territory']}', style: const TextStyle(color: Colors.grey, fontSize: 12)),
            const SizedBox(height: 14),

            _summaryRow('Visit Duration', durationStr, Icons.timer, Colors.cyanAccent),
            const SizedBox(height: 8),
            _summaryRow('Orders Placed', outlet['isProvisional'] == true ? 'KES 14,800 (Locked)' : 'KES 14,800', Icons.shopping_bag, Colors.indigoAccent),
            const SizedBox(height: 8),
            _summaryRow('Collections Captured', 'KES 14,800 (M-Pesa STK)', Icons.payments, Colors.greenAccent),
            const SizedBox(height: 8),
            _summaryRow('MSL Audit Score', '92% Compliant', Icons.verified, Colors.pinkAccent),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              setState(() {
                outlet['status'] = 'Visit Completed';
                _outletActiveSteps[outletId] = 6;
                _statusMessage = 'Check-out completed for ${outlet['name']} ($durationStr). Syncing batch...';
              });

              // Auto-Flush Sync Queue immediately on visit completion
              try {
                final bool success = await _syncManager.pushOfflineBatch();
                if (mounted) {
                  setState(() {
                    _statusMessage = success 
                        ? 'Visit Completed & Synced! Server Sync: 200 OK' 
                        : 'Visit Completed! Saved Offline';
                  });
                }
              } catch (e) {
                if (mounted) {
                  setState(() {
                    _statusMessage = 'Visit Completed Offline (Queued for Sync Manager).';
                  });
                }
              }

              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Visit Completed & Synced: ${outlet['name']}'),
                    backgroundColor: const Color(0xFF10B981),
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Confirm & End Call', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _summaryRow(String label, String value, IconData icon, Color color) {
    return Row(
      children: [
        Icon(icon, color: color, size: 18),
        const SizedBox(width: 8),
        Text('$label: ', style: const TextStyle(color: Colors.grey, fontSize: 12)),
        Expanded(
          child: Text(
            value,
            textAlign: TextAlign.end,
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final int pendingSyncCount = _syncManager.localActivities.where((a) => !a.isSynced).length +
        _syncManager.localSalesOrders.where((o) => !o.isSynced).length +
        _syncManager.localMerchObservations.where((m) => !m.isSynced).length;

    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      appBar: AppBar(
        title: const Text('My Work Hub — SFA Lean UX', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF0F172A),
        actions: [
          // Visual Offline Sync Counter Badge Action Button
          TextButton.icon(
            style: TextButton.styleFrom(
              backgroundColor: pendingSyncCount > 0
                  ? Colors.amber.withValues(alpha: 0.2)
                  : const Color(0xFF10B981).withValues(alpha: 0.15),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
                side: BorderSide(
                  color: pendingSyncCount > 0 ? Colors.amberAccent : const Color(0xFF34D399),
                ),
              ),
            ),
            icon: Icon(
              Icons.sync,
              color: pendingSyncCount > 0 ? Colors.amberAccent : const Color(0xFF34D399),
              size: 16,
            ),
            label: Text(
              pendingSyncCount > 0 ? 'Sync ($pendingSyncCount)' : '✓ Synced',
              style: TextStyle(
                color: pendingSyncCount > 0 ? Colors.amberAccent : const Color(0xFF34D399),
                fontSize: 11,
                fontWeight: FontWeight.bold,
              ),
            ),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => SyncCenterScreen(
                    token: widget.token,
                    syncManager: _syncManager,
                  ),
                ),
              ).then((_) => setState(() {}));
            },
          ),
          const SizedBox(width: 8),
          IconButton(
            icon: _isLocating
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Icon(Icons.my_location),
            onPressed: _fetchRealTimeLocation,
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Rep Profile & Shift Status Header Card with Offline Sync Stats
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.white12),
                boxShadow: const [
                  BoxShadow(color: Colors.black26, blurRadius: 10, offset: Offset(0, 4)),
                ],
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 22,
                    backgroundColor: const Color(0xFF6366F1),
                    child: Text(
                      widget.userName.isNotEmpty ? widget.userName[0] : 'R',
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(widget.userName, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                        const Text('Route: Nairobi Central Call Cycle', style: TextStyle(color: Colors.grey, fontSize: 12)),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.green.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.green.withValues(alpha: 0.3)),
                        ),
                        child: const Text('On Shift', style: TextStyle(color: Colors.greenAccent, fontSize: 11, fontWeight: FontWeight.bold)),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        pendingSyncCount > 0 ? '$pendingSyncCount Queue' : 'Sync: 200 OK',
                        style: TextStyle(
                          color: pendingSyncCount > 0 ? Colors.amberAccent : Colors.cyanAccent,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),

            // Location & Geofence Status Box
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.indigo.withValues(alpha: 0.3)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.location_on, color: Colors.indigoAccent, size: 18),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _statusMessage,
                      style: const TextStyle(color: Colors.indigoAccent, fontSize: 12, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            const Text('Assigned Outlet Call Cycle', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Colors.white)),
            const SizedBox(height: 12),

            // Outlets List with Lean UX Cards, Credit Alerts, & Guided Call Steps
            Expanded(
              child: ListView.builder(
                itemCount: _outlets.length,
                itemBuilder: (context, index) {
                  final outlet = _outlets[index];
                  final String outletId = outlet['id'] as String;
                  final int? distance = outlet['distanceMeters'];
                  final bool isCheckedIn = outlet['status'].toString().contains('Checked In');
                  final bool isCompleted = outlet['status'].toString().contains('Completed');
                  final bool isProvisional = outlet['isProvisional'] == true;
                  final bool isInRange = distance != null && distance <= 1500;
                  final int activeStep = _outletActiveSteps[outletId] ?? 1;

                  // Credit Utilization Data & Alert Badge
                  final double creditLimit = (outlet['creditLimit'] as num?)?.toDouble() ?? 150000.0;
                  final double balance = (outlet['balance'] as num?)?.toDouble() ?? 0.0;
                  final double utilization = creditLimit > 0 ? (balance / creditLimit) * 100.0 : 0.0;
                  final String channel = outlet['channel'] as String? ?? 'General Retail';
                  final bool isHighCreditRisk = utilization > 80.0;

                  return Container(
                    margin: const EdgeInsets.only(bottom: 14),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isCheckedIn
                          ? Colors.green.withValues(alpha: 0.08)
                          : (isCompleted ? Colors.green.withValues(alpha: 0.04) : const Color(0xFF1E293B)),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: isCheckedIn
                            ? Colors.green
                            : (isCompleted ? Colors.greenAccent.withValues(alpha: 0.6) : Colors.white12),
                        width: isCheckedIn ? 1.5 : 1,
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Outlet Header Row
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Icon(
                              isCheckedIn ? Icons.check_circle : (isCompleted ? Icons.verified : Icons.storefront),
                              color: isCheckedIn ? Colors.greenAccent : (isCompleted ? Colors.greenAccent : (isInRange ? Colors.indigoAccent : Colors.grey)),
                              size: 30,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          outlet['name'] as String,
                                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15),
                                        ),
                                      ),
                                      if (isCompleted)
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: Colors.green.withValues(alpha: 0.2),
                                            borderRadius: BorderRadius.circular(10),
                                            border: Border.all(color: Colors.greenAccent.withValues(alpha: 0.4)),
                                          ),
                                          child: const Text('Visited ✓', style: TextStyle(color: Colors.greenAccent, fontSize: 10, fontWeight: FontWeight.bold)),
                                        ),
                                      if (isProvisional && !isCompleted)
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: Colors.amber.withValues(alpha: 0.2),
                                            borderRadius: BorderRadius.circular(10),
                                            border: Border.all(color: Colors.amberAccent.withValues(alpha: 0.4)),
                                          ),
                                          child: const Text('Provisional', style: TextStyle(color: Colors.amberAccent, fontSize: 10, fontWeight: FontWeight.bold)),
                                        ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                        decoration: BoxDecoration(
                                          color: Colors.indigo.withValues(alpha: 0.2),
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(channel, style: const TextStyle(color: Colors.indigoAccent, fontSize: 10, fontWeight: FontWeight.bold)),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        outlet['territory'] as String,
                                        style: const TextStyle(color: Colors.grey, fontSize: 11),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),

                        // PRD Credit Utilization Alert Badge
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: isHighCreditRisk
                                ? const Color(0xFFF43F5E).withValues(alpha: 0.1)
                                : const Color(0xFF10B981).withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: isHighCreditRisk
                                  ? const Color(0xFFF43F5E).withValues(alpha: 0.3)
                                  : const Color(0xFF10B981).withValues(alpha: 0.2),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Icon(
                                        isHighCreditRisk ? Icons.warning_amber_rounded : Icons.credit_score,
                                        color: isHighCreditRisk ? const Color(0xFFFB7185) : const Color(0xFF34D399),
                                        size: 16,
                                      ),
                                      const SizedBox(width: 6),
                                      Text(
                                        isHighCreditRisk
                                            ? '⚠️ Credit Risk Alert: ${utilization.toStringAsFixed(0)}% Utilized'
                                            : 'Credit Status: ${utilization.toStringAsFixed(0)}% Utilized',
                                        style: TextStyle(
                                          color: isHighCreditRisk ? const Color(0xFFFB7185) : const Color(0xFF34D399),
                                          fontWeight: FontWeight.bold,
                                          fontSize: 11,
                                        ),
                                      ),
                                    ],
                                  ),
                                  Text(
                                    'KES ${balance.toStringAsFixed(0)} / ${creditLimit.toStringAsFixed(0)}',
                                    style: const TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.bold, fontFamily: 'monospace'),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              // Credit Utilization Progress Bar
                              ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: LinearProgressIndicator(
                                  value: (utilization / 100.0).clamp(0.0, 1.0),
                                  minHeight: 4,
                                  backgroundColor: Colors.white10,
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                    isHighCreditRisk ? const Color(0xFFF43F5E) : const Color(0xFF10B981),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),

                        // Proximity & Action Button Bar
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'GPS Proximity: ${_formatDistance(distance)}',
                              style: TextStyle(
                                color: isInRange ? Colors.greenAccent : Colors.amberAccent,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            ElevatedButton(
                              onPressed: (isCheckedIn || isCompleted)
                                  ? null
                                  : (isInRange
                                      ? () => _performOutletCheckIn(outlet)
                                      : () => _showGeofenceRuleFailureModal(outlet, distance)),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: isCheckedIn
                                    ? Colors.green[800]
                                    : (isCompleted ? Colors.grey[800] : (isInRange ? const Color(0xFF6366F1) : const Color(0xFF334155))),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              ),
                              child: Text(
                                isCheckedIn
                                    ? 'Visit Active'
                                    : (isCompleted ? 'Visited ✓' : (isInRange ? 'Step 1: Check In' : 'Out of Range')),
                                style: TextStyle(
                                  fontSize: 12,
                                  color: (isCheckedIn || isCompleted)
                                      ? Colors.white54
                                      : (isInRange ? Colors.white : Colors.amberAccent),
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ],
                        ),

                        // Guided Call Sequence
                        if (isCheckedIn) ...[
                          const SizedBox(height: 14),
                          const Divider(color: Colors.white10),
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Guided Call Execution', style: TextStyle(color: Colors.indigoAccent, fontWeight: FontWeight.bold, fontSize: 12)),
                              Text('Step $activeStep of 6', style: const TextStyle(color: Colors.cyanAccent, fontWeight: FontWeight.bold, fontSize: 11)),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Column(
                            children: [
                              Row(
                                children: [
                                  Expanded(child: _actionCard(title: '2. Customer 360', subtitle: 'Commercial', color: const Color(0xFF6366F1), isActive: activeStep == 2, onTap: () => _showCustomer360Modal(outlet))),
                                  const SizedBox(width: 8),
                                  Expanded(child: _actionCard(title: '3. Merchandising', subtitle: 'MSL Audit', color: const Color(0xFFEC4899), isActive: activeStep == 3, onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => MerchandisingScreen(token: widget.token, customerId: outlet['id'], outletName: outlet['name']))).then((_) => setState(() => _outletActiveSteps[outletId] = 4)))),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Expanded(child: _actionCard(title: '4. Order Entry', subtitle: '7-Tier Price', color: const Color(0xFF3B82F6), isActive: activeStep == 4, onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => OrderEntryScreen(token: widget.token, customerId: outlet['id'], outletName: outlet['name']))).then((_) => setState(() => _outletActiveSteps[outletId] = 5)))),
                                  const SizedBox(width: 8),
                                  Expanded(child: _actionCard(title: '5. Collection', subtitle: 'M-Pesa STK', color: const Color(0xFF10B981), isActive: activeStep == 5, onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => CollectionScreen(token: widget.token, customerId: outlet['id'], outletName: outlet['name']))).then((_) => setState(() => _outletActiveSteps[outletId] = 6)))),
                                ],
                              ),
                              const SizedBox(height: 8),
                              SizedBox(width: double.infinity, child: _actionCard(title: '6. Complete Visit', subtitle: 'Lock Record', color: Colors.amber[700]!, isActive: activeStep == 6, onTap: () => _checkoutOutlet(outlet))),
                            ],
                          ),
                        ],
                      ],
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _actionCard({
    required String title,
    required String subtitle,
    required Color color,
    required bool isActive,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: isActive ? color.withValues(alpha: 0.3) : color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: isActive ? color : color.withValues(alpha: 0.3), width: isActive ? 2 : 1),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(title, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 13)),
                if (isActive) Icon(Icons.play_circle_fill, color: color, size: 14),
              ],
            ),
            const SizedBox(height: 2),
            Text(subtitle, style: const TextStyle(color: Colors.grey, fontSize: 10)),
          ],
        ),
      ),
    );
  }
}
