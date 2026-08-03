// BARA Mobile Client - SyncManager Offline Sync Engine
// File: mobile_client/lib/services/sync_manager.dart

import '../config/api_config.dart';
import '../models/isar_models.dart';

class SyncManager {
  final String baseUrl;
  final String authToken;
  final String tenantHeader;
  int lastSyncedSequence;

  final List<IsarActivity> localActivities = [];
  final List<IsarTrackingPoint> localTrackingPoints = [];
  final List<IsarSalesOrder> localSalesOrders = [];
  final List<IsarMerchObservation> localMerchObservations = [];

  SyncManager({
    String? baseUrl,
    required this.authToken,
    String? tenantHeader,
    this.lastSyncedSequence = 0,
  })  : this.baseUrl = baseUrl ?? ApiConfig.baseUrl,
        this.tenantHeader = tenantHeader ?? ApiConfig.defaultTenant;

  /// Build 50-chunk push log payload from unsynced local models
  Map<String, dynamic> prepareChunkedPushPayload({int maxChunkSize = ApiConfig.syncBatchChunkSize}) {
    final List<Map<String, dynamic>> pushLogs = [];

    // Pack un-synced activities
    for (var act in localActivities.where((a) => !a.isSynced)) {
      if (pushLogs.length >= maxChunkSize) break;
      pushLogs.add({
        'client_uuid': act.clientUuid,
        'sequence': act.sequence,
        'entity_type': 'activity',
        'payload': act.toSyncPayload(),
      });
    }

    // Pack un-synced orders
    for (var order in localSalesOrders.where((o) => !o.isSynced)) {
      if (pushLogs.length >= maxChunkSize) break;
      pushLogs.add({
        'client_uuid': order.clientUuid,
        'sequence': order.sequence,
        'entity_type': 'sales_order',
        'payload': order.toSyncPayload(),
      });
    }

    return {
      'chunk_size': pushLogs.length,
      'logs': pushLogs,
    };
  }

  /// Process push response and mark client UUIDs as synced
  void handlePushResponse(Map<String, dynamic> responseData) {
    final List<dynamic> processedUuids = responseData['processed_uuids'] ?? [];

    for (var uuid in processedUuids) {
      for (var act in localActivities) {
        if (act.clientUuid == uuid) act.isSynced = true;
      }
      for (var order in localSalesOrders) {
        if (order.clientUuid == uuid) order.isSynced = true;
      }
    }
  }

  /// Process pull deltas with Last-Write-Wins (LWW) resolution
  void handlePullDeltasResponse(Map<String, dynamic> responseData) {
    final Map<String, dynamic> deltas = responseData['deltas'] ?? {};
    final List<dynamic> serverActivities = deltas['activities'] ?? [];

    for (var serverAct in serverActivities) {
      final String uuid = serverAct['client_uuid'] ?? '';
      final int serverSeq = serverAct['sequence'] ?? 0;

      final existingIndex = localActivities.indexWhere((a) => a.clientUuid == uuid);
      if (existingIndex >= 0) {
        // LWW conflict check: apply server state if server sequence is >= local sequence
        if (serverSeq >= localActivities[existingIndex].sequence) {
          localActivities[existingIndex]
            ..status = serverAct['status'] ?? localActivities[existingIndex].status
            ..sequence = serverSeq
            ..isSynced = true;
        }
      } else if (uuid.isNotEmpty) {
        // Insert new record pulled from server
        localActivities.add(IsarActivity(
          id: localActivities.length + 1,
          clientUuid: uuid,
          sequence: serverSeq,
          referenceNo: serverAct['reference_no'] ?? 'ACT-PULL-00',
          activityType: serverAct['activity_type'] ?? 'task',
          title: serverAct['title'] ?? 'Pulled Activity',
          status: serverAct['status'] ?? 'pending',
          isSynced: true,
        ));
      }
    }

    lastSyncedSequence = responseData['latest_sequence'] ?? lastSyncedSequence;
  }
}
