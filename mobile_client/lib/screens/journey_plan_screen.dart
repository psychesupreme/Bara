import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import '../config/api_config.dart';
import '../services/sync_manager.dart';
import '../services/field_operations_adapter.dart';
import 'order_entry_screen.dart';
import 'collection_screen.dart';

class JourneyPlanScreen extends StatefulWidget {
  final String token;
  final String userName;

  const JourneyPlanScreen({super.key, required this.token, required this.userName});

  @override
  State<JourneyPlanScreen> createState() => _JourneyPlanScreenState();
}

class _JourneyPlanScreenState extends State<JourneyPlanScreen> {
  late final FieldOperationsAdapter _adapter;
  Position? _currentPosition;
  bool _isLocating = false;
  String _statusMessage = 'Call Cycle Loaded | Select an outlet to verify geofence';

  // Seeded Outlets for Nairobi Central Route
  final List<Map<String, dynamic>> _outlets = [
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
    final syncManager = SyncManager(
      baseUrl: ApiConfig.baseUrl,
      authToken: widget.token,
      tenantHeader: ApiConfig.defaultTenant,
    );
    _adapter = FieldOperationsAdapter(syncManager: syncManager);
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

  void _performOutletCheckIn(Map<String, dynamic> outlet) {
    final int distance = outlet['distanceMeters'] ?? 9999;
    final bool inGeofence = distance <= 1500; // 1.5km geofence tolerance for Nairobi pilot

    if (!inGeofence) {
      showDialog(
        context: context,
        builder: (_) => AlertDialog(
          backgroundColor: const Color(0xFF1E293B),
          title: const Text('Geofence Alert', style: TextStyle(color: Colors.redAccent)),
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
                'You are ${_formatDistance(distance)} away from this outlet (${outlet['lat']}, ${outlet['lng']}). Geofence check-in requires <= 1.5 km proximity.',
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
      return;
    }

    final activity = _adapter.checkInOutlet(
      clientUuid: 'UUID-${DateTime.now().millisecondsSinceEpoch}',
      customerId: outlet['id'] as String,
      outletName: outlet['name'] as String,
      latitude: _currentPosition?.latitude ?? outlet['lat'] as double,
      longitude: _currentPosition?.longitude ?? outlet['lng'] as double,
    );

    setState(() {
      outlet['status'] = 'Checked In (Active Visit)';
      _statusMessage = 'Check-in Recorded! Reference: ${activity.referenceNo}';
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Visit Started: ${outlet['name']} (${activity.referenceNo})'),
        backgroundColor: const Color(0xFF10B981),
      ),
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

                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isCheckedIn ? Colors.green.withValues(alpha: 0.1) : const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: isCheckedIn ? Colors.green : Colors.white10),
                    ),
                    child: Column(
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Icon(
                              isCheckedIn ? Icons.check_circle : Icons.storefront,
                              color: isCheckedIn ? Colors.greenAccent : Colors.indigoAccent,
                              size: 32,
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    outlet['name'] as String,
                                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
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
                                      color: (distance != null && distance <= 1500) ? Colors.greenAccent : Colors.amberAccent,
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            ElevatedButton(
                              onPressed: () => _performOutletCheckIn(outlet),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: isCheckedIn ? Colors.grey[700] : const Color(0xFF6366F1),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                              child: Text(isCheckedIn ? 'Visit Active' : 'Check In', style: const TextStyle(fontSize: 12, color: Colors.white)),
                            ),
                          ],
                        ),
                        if (isCheckedIn) ...[
                          const SizedBox(height: 12),
                          const Divider(color: Colors.white10),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () {
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
                                  icon: const Icon(Icons.add_shopping_cart, size: 16, color: Colors.indigoAccent),
                                  label: const Text('Order Entry', style: TextStyle(color: Colors.indigoAccent, fontSize: 12)),
                                  style: OutlinedButton.styleFrom(
                                    side: const BorderSide(color: Colors.indigoAccent),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () {
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
                                  icon: const Icon(Icons.payments, size: 16, color: Colors.greenAccent),
                                  label: const Text('Collection', style: TextStyle(color: Colors.greenAccent, fontSize: 12)),
                                  style: OutlinedButton.styleFrom(
                                    side: const BorderSide(color: Colors.greenAccent),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  ),
                                ),
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
}
