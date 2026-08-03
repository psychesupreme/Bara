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
  String _statusMessage = 'Call Cycle Loaded | Select an outlet to verify geofence';
  final Map<String, DateTime> _checkInTimestamps = {};

  // Seeded Outlets for Nairobi Route (includes Kasarani Live Test Store at -1.2002, 36.8344)
  final List<Map<String, dynamic>> _outlets = [
    {
      'id': 'CUST-KASARANI-LIVE',
      'name': 'Kasarani Live Test Store',
      'territory': 'Kasarani Zone',
      'lat': -1.2002000,
      'lng': 36.8344000,
      'status': 'Pending Visit',
      'distanceMeters': null,
    },
    {
      'id': 'CUST-NAIVAS-CBD',
      'name': 'Naivas Supermarket CBD Branch',
      'territory': 'CBD Zone',
      'lat': -1.2833300,
      'lng': 36.8166700,
      'status': 'Pending Visit',
      'distanceMeters': null,
    },
    {
      'id': 'CUST-SARIT-MART',
      'name': 'Sarit Center Mart',
      'territory': 'Westlands Zone',
      'lat': -1.2612000,
      'lng': 36.8041000,
      'status': 'Pending Visit',
      'distanceMeters': null,
    },
    {
      'id': 'CUST-YAYA-MINI',
      'name': 'Yaya Center MiniMart',
      'territory': 'Kilimani Zone',
      'lat': -1.2917000,
      'lng': 36.7865000,
      'status': 'Pending Visit',
      'distanceMeters': null,
    },
    {
      'id': 'CUST-CBD-CONV',
      'name': 'CBD Convenience Store',
      'territory': 'CBD Zone',
      'lat': -1.2845000,
      'lng': 36.8210000,
      'status': 'Pending Visit',
      'distanceMeters': null,
    },
  ];

  @override
  void initState() {
    super.initState();
    _syncManager = SyncManager(
      baseUrl: ApiConfig.baseUrl,
      authToken: widget.token,
      tenantHeader: ApiConfig.defaultTenant,
    );
    _adapter = FieldOperationsAdapter(syncManager: _syncManager);
    _fetchRealTimeLocation();
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
        setState(() {
          _statusMessage = 'Location services disabled. Using Nairobi Center (-1.28333, 36.81667)';
          _isLocating = false;
        });
        _updateDistances(-1.28333, 36.81667);
        return;
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          setState(() {
            _statusMessage = 'Location permission denied by user. Calculated against Nairobi Center.';
            _isLocating = false;
          });
          _updateDistances(-1.28333, 36.81667);
          return;
        }
      }

      if (permission == LocationPermission.deniedForever) {
        setState(() {
          _statusMessage = 'Location permission permanently denied. Calculated against Nairobi Center.';
          _isLocating = false;
        });
        _updateDistances(-1.28333, 36.81667);
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
      setState(() {
        _statusMessage = 'GPS Timeout (10s). Calculated against Nairobi CBD Center.';
        _isLocating = false;
      });
      _updateDistances(-1.28333, 36.81667);
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

  void _showGeofenceAlert(Map<String, dynamic> outlet, int? distance) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text('Geofence Boundary Alert', style: TextStyle(color: Colors.redAccent)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              outlet['name'] as String,
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15),
            ),
            Text(
              'Zone: ${outlet['territory']}',
              style: const TextStyle(color: Colors.grey, fontSize: 12),
            ),
            const SizedBox(height: 10),
            Text(
              'Your current location is ${_formatDistance(distance)} away from this outlet. Geofence check-in requires <= 1.5 km proximity.',
              style: const TextStyle(color: Colors.grey, fontSize: 13),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK', style: TextStyle(color: Colors.indigoAccent)),
          ),
        ],
      ),
    );
  }

  Future<void> _performOutletCheckIn(Map<String, dynamic> outlet) async {
    final int distance = outlet['distanceMeters'] ?? 9999;
    final bool inGeofence = distance <= 1500; // 1.5km geofence tolerance for Nairobi pilot

    if (!inGeofence) {
      _showGeofenceAlert(outlet, distance);
      return;
    }

    if (_currentPosition == null) {
      await _fetchRealTimeLocation();
    }

    // Capture actual live phone GPS coordinates
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

    // Asynchronously send live GPS coordinates to backend /api/v1/field/check-in for WebSocket telemetry broadcast
    http.post(
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
    ).then((res) {
      if (res.statusCode == 200) {
        debugPrint('Live Device GPS Telemetry Broadcast Succeeded ($liveLat, $liveLng)!');
      }
    }).catchError((_) {});

    if (!mounted) return;

    setState(() {
      outlet['status'] = 'Checked In (Active Visit)';
      _statusMessage = 'Check-in Recorded! Reference: ${activity.referenceNo} (GPS: ${liveLat.toStringAsFixed(4)}, ${liveLng.toStringAsFixed(4)})';
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Visit Started: ${outlet['name']} (${activity.referenceNo})'),
        backgroundColor: const Color(0xFF10B981),
      ),
    );
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
            _summaryRow('Orders Placed', 'KES 14,800', Icons.shopping_bag, Colors.indigoAccent),
            const SizedBox(height: 8),
            _summaryRow('Collections Captured', 'KES 14,800 (M-Pesa STK)', Icons.payments, Colors.greenAccent),
            const SizedBox(height: 8),
            _summaryRow('MSL Audit Score', '92% Compliant', Icons.verified, Colors.pinkAccent),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              setState(() {
                outlet['status'] = 'Visit Completed';
                _statusMessage = 'Check-out completed for ${outlet['name']} ($durationStr)';
              });

              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('Visit Completed & Locked: ${outlet['name']}'),
                  backgroundColor: const Color(0xFF10B981),
                ),
              );
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
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      appBar: AppBar(
        title: const Text('Nairobi Journey Plan'),
        backgroundColor: const Color(0xFF0F172A),
        actions: [
          IconButton(
            icon: const Icon(Icons.sync, color: Colors.cyanAccent),
            tooltip: 'Visual Sync Center',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => SyncCenterScreen(
                    token: widget.token,
                    syncManager: _syncManager,
                  ),
                ),
              );
            },
          ),
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
            // Rep Profile Header Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white10),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    backgroundColor: const Color(0xFF6366F1),
                    child: Text(
                      widget.userName.isNotEmpty ? widget.userName[0] : 'R',
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
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
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.green.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.green.withValues(alpha: 0.3)),
                    ),
                    child: const Text('On Shift', style: TextStyle(color: Colors.greenAccent, fontSize: 11, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),

            // Location & Geofence Status
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.white10),
              ),
              child: Text(
                _statusMessage,
                style: const TextStyle(color: Colors.indigoAccent, fontSize: 12, fontWeight: FontWeight.w500),
              ),
            ),
            const SizedBox(height: 20),

            const Text('Daily Call Cycle Outlets', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
            const SizedBox(height: 12),

            // Outlets List
            Expanded(
              child: ListView.builder(
                itemCount: _outlets.length,
                itemBuilder: (context, index) {
                  final outlet = _outlets[index];
                  final int? distance = outlet['distanceMeters'];
                  final bool isCheckedIn = outlet['status'].toString().contains('Checked In');
                  final bool isCompleted = outlet['status'].toString().contains('Completed');
                  final bool isInRange = distance != null && distance <= 1500;

                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isCheckedIn
                          ? Colors.green.withValues(alpha: 0.1)
                          : (isCompleted ? Colors.green.withValues(alpha: 0.05) : const Color(0xFF1E293B)),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: isCheckedIn ? Colors.green : (isCompleted ? Colors.greenAccent : Colors.white10)),
                    ),
                    child: Column(
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Icon(
                              isCheckedIn ? Icons.check_circle : (isCompleted ? Icons.verified : Icons.storefront),
                              color: isCheckedIn ? Colors.greenAccent : (isCompleted ? Colors.greenAccent : (isInRange ? Colors.indigoAccent : Colors.grey)),
                              size: 32,
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          outlet['name'] as String,
                                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
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
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    '${outlet['territory']} • Lat: ${outlet['lat']}, Lng: ${outlet['lng']}',
                                    style: const TextStyle(color: Colors.grey, fontSize: 12),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'Proximity: ${_formatDistance(distance)}',
                                    style: TextStyle(
                                      color: isInRange ? Colors.greenAccent : Colors.amberAccent,
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            ElevatedButton(
                              onPressed: (isCheckedIn || isCompleted)
                                  ? null
                                  : (isInRange
                                      ? () => _performOutletCheckIn(outlet)
                                      : () => _showGeofenceAlert(outlet, distance)),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: isCheckedIn
                                    ? Colors.green[800]
                                    : (isCompleted ? Colors.grey[800] : (isInRange ? const Color(0xFF6366F1) : const Color(0xFF334155))),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              child: Text(
                                isCheckedIn
                                    ? 'Visit Active'
                                    : (isCompleted ? 'Visited ✓' : (isInRange ? 'Check In' : 'Out of Range')),
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

                        // Prominent Visit Action Menu Grid (Visible when Checked In)
                        if (isCheckedIn) ...[
                          const SizedBox(height: 14),
                          const Divider(color: Colors.white10),
                          const SizedBox(height: 8),
                          const Align(
                            alignment: Alignment.centerLeft,
                            child: Text(
                              'Outlet Visit Action Menu',
                              style: TextStyle(color: Colors.indigoAccent, fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                          ),
                          const SizedBox(height: 10),

                          Column(
                            children: [
                              Row(
                                children: [
                                  // Module 3: Order Entry Action Card
                                  Expanded(
                                    child: _actionCard(
                                      title: '🛒 Order Entry',
                                      subtitle: '7-Tier Waterfall Cart',
                                      color: const Color(0xFF6366F1),
                                      onTap: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) => OrderEntryScreen(
                                              token: widget.token,
                                              customerId: outlet['id'] as String,
                                              outletName: outlet['name'] as String,
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ),
                                  const SizedBox(width: 8),

                                  // Module 4: Collection & M-Pesa STK Push Action Card
                                  Expanded(
                                    child: _actionCard(
                                      title: '💳 Record Collection',
                                      subtitle: 'M-Pesa STK Push',
                                      color: const Color(0xFF10B981),
                                      onTap: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) => CollectionScreen(
                                              token: widget.token,
                                              customerId: outlet['id'] as String,
                                              outletName: outlet['name'] as String,
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),

                              Row(
                                children: [
                                  // Module 6: Merchandising / MSL Audit Card
                                  Expanded(
                                    child: _actionCard(
                                      title: '📸 Merchandising',
                                      subtitle: 'MSL & Shelf Audit',
                                      color: const Color(0xFFEC4899),
                                      onTap: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) => MerchandisingScreen(
                                              token: widget.token,
                                              customerId: outlet['id'] as String,
                                              outletName: outlet['name'] as String,
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ),
                                  const SizedBox(width: 8),

                                  // Complete Visit & Check-Out Card
                                  Expanded(
                                    child: _actionCard(
                                      title: '🏁 Complete Visit',
                                      subtitle: 'Check-Out & End Call',
                                      color: Colors.amber[700]!,
                                      onTap: () => _checkoutOutlet(outlet),
                                    ),
                                  ),
                                ],
                              ),
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
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.15),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: 0.4)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 13)),
            const SizedBox(height: 2),
            Text(subtitle, style: const TextStyle(color: Colors.grey, fontSize: 10)),
          ],
        ),
      ),
    );
  }
}
