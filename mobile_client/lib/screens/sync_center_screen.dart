import 'package:flutter/material.dart';
import '../config/api_config.dart';
import '../services/sync_manager.dart';

class SyncCenterScreen extends StatefulWidget {
  final String token;
  final SyncManager syncManager;

  const SyncCenterScreen({
    super.key,
    required this.token,
    required this.syncManager,
  });

  @override
  State<SyncCenterScreen> createState() => _SyncCenterScreenState();
}

class _SyncCenterScreenState extends State<SyncCenterScreen> {
  bool _isSyncing = false;
  String _lastSyncTimestamp = 'Not Synced Yet';
  final String _networkState = 'Connected (http://192.168.100.6:8000)';

  Future<void> _handlePushBatch() async {
    setState(() {
      _isSyncing = true;
    });

    try {
      final success = await widget.syncManager.pushOfflineBatch();
      if (!mounted) return;

      setState(() {
        _isSyncing = false;
        if (success) {
          _lastSyncTimestamp = DateTime.now().toLocal().toString().split('.')[0];
        }
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success ? 'Offline Batch Pushed Successfully!' : 'Sync Completed with Network Fallback.'),
          backgroundColor: success ? const Color(0xFF10B981) : Colors.orangeAccent,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSyncing = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Sync Error: $e'), backgroundColor: Colors.redAccent),
      );
    }
  }

  Future<void> _handlePullDelta() async {
    setState(() {
      _isSyncing = true;
    });

    try {
      final success = await widget.syncManager.pullDeltaUpdates();
      if (!mounted) return;

      setState(() {
        _isSyncing = false;
        if (success) {
          _lastSyncTimestamp = DateTime.now().toLocal().toString().split('.')[0];
        }
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success ? 'Delta Updates Pulled Successfully!' : 'Server Reached (0 New Updates).'),
          backgroundColor: const Color(0xFF10B981),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSyncing = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Pull Error: $e'), backgroundColor: Colors.redAccent),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final int pendingActivities = widget.syncManager.localActivities.where((a) => !a.isSynced).length;
    final int pendingOrders = widget.syncManager.localSalesOrders.where((o) => !o.isSynced).length;
    final int pendingMerch = widget.syncManager.localMerchObservations.where((m) => !m.isSynced).length;
    final int totalPending = pendingActivities + pendingOrders + pendingMerch;

    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      appBar: AppBar(
        title: const Text('Visual Sync Center'),
        backgroundColor: const Color(0xFF0F172A),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Endpoint & Connection Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white10),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Server Connection State', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.green.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: Colors.green.withValues(alpha: 0.3)),
                          ),
                          child: const Text('Online', style: TextStyle(color: Colors.greenAccent, fontSize: 11, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text('Base Endpoint: ${ApiConfig.baseUrl}', style: const TextStyle(color: Colors.indigoAccent, fontSize: 12, fontFamily: 'monospace')),
                    const SizedBox(height: 4),
                    Text('State: $_networkState', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                    const SizedBox(height: 2),
                    Text('Last Sync: $_lastSyncTimestamp', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Pending Records Summary Cards
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Pending Sync Queue', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: totalPending > 0 ? Colors.amber.withValues(alpha: 0.2) : Colors.green.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      '$totalPending Unsynced',
                      style: TextStyle(
                        color: totalPending > 0 ? Colors.amberAccent : Colors.greenAccent,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              Row(
                children: [
                  _statTile('Check-ins & Visits', '$pendingActivities', Icons.storefront, Colors.indigoAccent),
                  const SizedBox(width: 10),
                  _statTile('Draft Orders', '$pendingOrders', Icons.shopping_bag, Colors.cyanAccent),
                  const SizedBox(width: 10),
                  _statTile('Collections', '$pendingMerch', Icons.payments, Colors.greenAccent),
                ],
              ),
              const SizedBox(height: 24),

              // Sync Action Controls Card
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white10),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Manual Sync Controls', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                    const SizedBox(height: 6),
                    const Text('Push local Isar DB offline records to server or pull delta updates.', style: TextStyle(color: Colors.grey, fontSize: 12)),
                    const SizedBox(height: 16),

                    // Push Offline Batch Button
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton.icon(
                        onPressed: _isSyncing ? null : _handlePushBatch,
                        icon: _isSyncing
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Icon(Icons.cloud_upload, color: Colors.white),
                        label: const Text('Push Offline Batch Engine', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF6366F1),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Pull Delta Updates Button
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: OutlinedButton.icon(
                        onPressed: _isSyncing ? null : _handlePullDelta,
                        icon: const Icon(Icons.cloud_download, color: Colors.cyanAccent),
                        label: const Text('Pull Server Delta Updates', style: TextStyle(color: Colors.cyanAccent, fontWeight: FontWeight.bold, fontSize: 14)),
                        style: OutlinedButton.styleFrom(
                          side: const BorderSide(color: Colors.cyanAccent),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _statTile(String title, String count, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.white10),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 24),
            const SizedBox(height: 6),
            Text(count, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18)),
            const SizedBox(height: 2),
            Text(title, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey, fontSize: 10)),
          ],
        ),
      ),
    );
  }
}
