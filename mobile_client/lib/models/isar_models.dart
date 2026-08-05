// BARA Mobile Client - Isar DB Local Storage Models
// File: mobile_client/lib/models/isar_models.dart

import 'package:isar/isar.dart';

part 'isar_models.g.dart';

@collection
class IsarActivity {
  Id id = Isar.autoIncrement;

  @Index()
  late String clientUuid;

  late int sequence;
  late String referenceNo;
  late String activityType;
  late String title;
  late String status;
  String? customerId;
  String? outcomeCode;
  double? latitude;
  double? longitude;
  
  @Index()
  bool isSynced = false;
  
  DateTime? createdAt;

  IsarActivity({
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

@collection
class IsarTrackingPoint {
  Id id = Isar.autoIncrement;

  late String sessionId;
  late double latitude;
  late double longitude;
  late DateTime recordedAt;

  @Index()
  bool isSynced = false;

  IsarTrackingPoint({
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

@collection
class IsarSalesOrder {
  Id id = Isar.autoIncrement;

  @Index()
  late String clientUuid;

  late int sequence;
  late String orderNumber;
  late String customerId;
  late double totalAmount;
  late String status;
  late bool isOfflineCaptured;
  
  @Index()
  bool isSynced = false;
  
  DateTime? createdAt;

  IsarSalesOrder({
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

@collection
class IsarMerchObservation {
  Id id = Isar.autoIncrement;

  @Index()
  late String clientUuid;

  late int sequence;
  late String customerId;
  late double mslComplianceScore;
  late double shareOfShelfPercentage;
  String? evidencePhotoUrl;
  
  @Index()
  bool isSynced = false;
  
  DateTime? createdAt;

  IsarMerchObservation({
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
