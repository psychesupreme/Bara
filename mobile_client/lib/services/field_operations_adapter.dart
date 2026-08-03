// BARA Mobile Client - Field Operations Adapter
// File: mobile_client/lib/services/field_operations_adapter.dart

import '../models/isar_models.dart';
import 'sync_manager.dart';

class FieldOperationsAdapter {
  final SyncManager syncManager;

  FieldOperationsAdapter({required this.syncManager});

  /// Offline Visit Check-in
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
      isSynced: false,
    );

    syncManager.localActivities.add(activity);
    return activity;
  }

  /// Offline Sales Order Drafting with local pricing calculation
  IsarSalesOrder draftSalesOrder({
    required String clientUuid,
    required String customerId,
    required List<Map<String, dynamic>> items,
  }) {
    double totalAmount = 0.0;
    for (var item in items) {
      final double qty = (item['quantity'] as num).toDouble();
      final double unitPrice = (item['unit_price'] as num).toDouble();
      totalAmount += (qty * unitPrice);
    }

    final order = IsarSalesOrder(
      id: syncManager.localSalesOrders.length + 1,
      clientUuid: clientUuid,
      sequence: 1,
      orderNumber: 'SO-OFFLINE-${DateTime.now().millisecondsSinceEpoch}',
      customerId: customerId,
      totalAmount: totalAmount,
      status: 'draft',
      isOfflineCaptured: true,
      isSynced: false,
    );

    syncManager.localSalesOrders.add(order);
    return order;
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
