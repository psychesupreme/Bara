// BARA Mobile Client - Local Network API & WebSocket Configuration
// File: mobile_client/lib/config/api_config.dart

class ApiConfig {
  static const String hostIp = '192.168.100.6';
  static const int apiPort = 8000;
  static const int webSocketPort = 8080;

  /// Base URL for REST API endpoints
  static const String baseUrl = 'http://$hostIp:$apiPort/api/v1';

  /// Reverb WebSocket Endpoint
  static const String webSocketUrl = 'ws://$hostIp:$webSocketPort/app';

  /// Default Tenant Header for single/multi-tenant resolution
  static const String defaultTenant = 'tenant1';

  /// Isar DB Sync Batch Chunk Size
  static const int syncBatchChunkSize = 50;

  /// HTTP Headers builder with Sanctum Token & Tenant ID
  static Map<String, String> getHeaders({String? bearerToken, String? tenantId}) {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Tenant': tenantId ?? defaultTenant,
      if (bearerToken != null && bearerToken.isNotEmpty)
        'Authorization': 'Bearer $bearerToken',
    };
  }
}
