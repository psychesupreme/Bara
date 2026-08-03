// BARA Mobile Client - Isar DB Local Storage Models
// File: mobile_client/lib/models/isar_models.dart

class IsarActivity {
  int id;
  String clientUuid;
  int sequence;
  String referenceNo;
  String activityType;
  String title;
  String status;
  String? customerId;
  String? outcomeCode;
  double? latitude;
  double? longitude;
  bool isSynced;
  DateTime createdAt;

  IsarActivity({
    required this.id,
    required this.clientUuid,
    required this.sequence,
    required this.referenceNo,
    required this.activityType,
    required this.title,
    required this.status,
    this.customerId,
    this.outcomeCode,
    this.latitude,
    this.longitude,
    this.isSynced = false,
    DateTime? createdAt,
  }) : createdAt = createdAt ?? DateTime.now();

  Map<String, dynamic> toSyncPayload() {
    return {
      'client_uuid': clientUuid,
      'sequence': sequence,
      'reference_no': referenceNo,
      'activity_type': activityType,
      'title': title,
      'status': status,
      'customer_id': customerId,
      'outcome_code': outcomeCode,
      'latitude': latitude,
      'longitude': longitude,
    };
  }
}

class IsarTrackingPoint {
  int id;
  String sessionId;
  double latitude;
  double longitude;
  DateTime recordedAt;
  bool isSynced;

  IsarTrackingPoint({
    required this.id,
    required this.sessionId,
    required this.latitude,
    required this.longitude,
    required this.recordedAt,
    this.isSynced = false,
  });

  Map<String, dynamic> toSyncPayload() {
    return {
      'session_id': sessionId,
      'latitude': latitude,
      'longitude': longitude,
      'recorded_at': recordedAt.toIso8601String(),
    };
  }
}

class IsarSalesOrder {
  int id;
  String clientUuid;
  int sequence;
  String orderNumber;
  String customerId;
  double totalAmount;
  String status;
  bool isOfflineCaptured;
  bool isSynced;
  DateTime createdAt;

  IsarSalesOrder({
    required this.id,
    required this.clientUuid,
    required this.sequence,
    required this.orderNumber,
    required this.customerId,
    required this.totalAmount,
    required this.status,
    required this.isOfflineCaptured,
    this.isSynced = false,
    DateTime? createdAt,
  }) : createdAt = createdAt ?? DateTime.now();

  Map<String, dynamic> toSyncPayload() {
    return {
      'client_uuid': clientUuid,
      'sequence': sequence,
      'order_number': orderNumber,
      'customer_id': customerId,
      'total_amount': totalAmount,
      'status': status,
      'is_offline_captured': isOfflineCaptured,
    };
  }
}

class IsarMerchObservation {
  int id;
  String clientUuid;
  int sequence;
  String customerId;
  double mslComplianceScore;
  double shareOfShelfPercentage;
  String? evidencePhotoUrl;
  bool isSynced;
  DateTime createdAt;

  IsarMerchObservation({
    required this.id,
    required this.clientUuid,
    required this.sequence,
    required this.customerId,
    required this.mslComplianceScore,
    required this.shareOfShelfPercentage,
    this.evidencePhotoUrl,
    this.isSynced = false,
    DateTime? createdAt,
  }) : createdAt = createdAt ?? DateTime.now();

  Map<String, dynamic> toSyncPayload() {
    return {
      'client_uuid': clientUuid,
      'sequence': sequence,
      'customer_id': customerId,
      'msl_compliance_score': mslComplianceScore,
      'share_of_shelf_percentage': shareOfShelfPercentage,
      'evidence_photo_url': evidencePhotoUrl,
    };
  }
}
