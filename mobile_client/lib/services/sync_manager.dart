// BARA Mobile Client - SyncManager Offline Sync Engine
// File: mobile_client/lib/services/sync_manager.dart

import 'package:http/http.dart' as http;
import 'dart:convert';
import '../config/api_config.dart';
import '../models/isar_models.dart';

class SyncManager {
  static SyncManager? _instance;
  static SyncManager get instance => _instance!;
  static void initialize({required String authToken, String? baseUrl, String? tenantHeader}) {
    _instance = SyncManager(
      authToken: authToken,
      baseUrl: baseUrl,
      tenantHeader: tenantHeader,
    );
  }
  static bool get isInitialized => _instance != null;

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
  })  : baseUrl = baseUrl ?? ApiConfig.baseUrl,
        tenantHeader = tenantHeader ?? ApiConfig.defaultTenant;

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
  }

  /// Execute Push Offline Batch HTTP request
  Future<bool> pushOfflineBatch() async {
    try {
      final payload = prepareChunkedPushPayload();
      if ((payload['chunk_size'] as int) == 0) {
        return true; // Nothing to push
      }

      final response = await http.post(
        Uri.parse('$baseUrl/sync/push-logs'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $authToken',
          'X-Tenant': tenantHeader,
        },
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = jsonDecode(response.body);
        handlePushResponse(data);
        return true;
      }
    } catch (e) {
      // Network failure — do NOT mark items as synced
      // They remain in queue for retry on next pushOfflineBatch() call
      return false;
    }
    return false;
  }

  /// Execute Pull Delta Updates HTTP request
  Future<bool> pullDeltaUpdates() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/sync/pull-deltas?since_sequence=$lastSyncedSequence'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $authToken',
          'X-Tenant': tenantHeader,
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        handlePullDeltasResponse(data);
        return true;
      }
    } catch (e) {
      return false;
    }
    return false;
  }
}
