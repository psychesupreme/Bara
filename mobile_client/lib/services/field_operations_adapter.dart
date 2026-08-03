// BARA Mobile Client - Field Operations Adapter
// File: mobile_client/lib/services/field_operations_adapter.dart

import '../models/isar_models.dart';
import 'sync_manager.dart';

class FieldOperationsAdapter {
  final SyncManager syncManager;

  FieldOperationsAdapter({required this.syncManager});

  /// Offline Visit Check-in with live GPS coordinates
  IsarActivity checkInOutlet({
    required String clientUuid,
    required String customerId,
    required String outletName,
    required double latitude,
    required double longitude,
  }) {
    final activity = IsarActivity(
      id: syncManager.localActivities.length + 1,
      clientUuid: clientUuid,
      sequence: 1,
      referenceNo: 'ACT-CHK-${DateTime.now().millisecondsSinceEpoch}',
      activityType: 'visit',
      title: 'Store Check-in: $outletName',
      status: 'in_progress',
      customerId: customerId,
      latitude: latitude,
      longitude: longitude,
      isSynced: false,
    );

    syncManager.localActivities.add(activity);
    return activity;
  }

  /// Offline Sales Order Drafting with 7-tier pricing calculation
  IsarSalesOrder createDraftOrder({
    required String clientUuid,
    required String customerId,
    required String outletName,
    required List<Map<String, dynamic>> items,
    required double totalAmount,
  }) {
    final order = IsarSalesOrder(
      id: syncManager.localSalesOrders.length + 1,
      clientUuid: clientUuid,
      sequence: 1,
      orderNumber: 'SO-${DateTime.now().millisecondsSinceEpoch}',
      customerId: customerId,
      totalAmount: totalAmount,
      status: 'draft',
      isOfflineCaptured: true,
      isSynced: false,
    );

    syncManager.localSalesOrders.add(order);
    return order;
  }

  /// Offline Collection / Payment Receipt Entry
  IsarActivity createDraftCollection({
    required String clientUuid,
    required String customerId,
    required String outletName,
    required double amount,
    required String paymentMethod,
    required String referenceNo,
  }) {
    final collection = IsarActivity(
      id: syncManager.localActivities.length + 1,
      clientUuid: clientUuid,
      sequence: 1,
      referenceNo: referenceNo,
      activityType: 'collection',
      title: 'Payment Collection ($paymentMethod): KES ${amount.toStringAsFixed(2)} - $outletName',
      status: 'completed',
      customerId: customerId,
      isSynced: false,
    );

    syncManager.localActivities.add(collection);
    return collection;
  }

  /// Merchandising Observation Capture (MSL Availability & Share of Shelf)
  IsarMerchObservation recordMerchandisingObservation({
    required String clientUuid,
    required String customerId,
    required double mslScore,
    required double shareOfShelf,
    String? photoUrl,
  }) {
    final obs = IsarMerchObservation(
      id: syncManager.localMerchObservations.length + 1,
      clientUuid: clientUuid,
      sequence: 1,
      customerId: customerId,
      mslComplianceScore: mslScore,
      shareOfShelfPercentage: shareOfShelf,
      evidencePhotoUrl: photoUrl,
      isSynced: false,
    );

    syncManager.localMerchObservations.add(obs);
    return obs;
  }
}
