<?php

namespace Tests\Feature;

use App\Models\FieldDevice;
use App\Models\FieldLocation;
use App\Models\TrackingSession;
use App\Models\User;
use App\Services\AttendanceAdapters\AttendanceAdapterFactory;
use App\Services\DeviceLifecycleService;
use App\Services\LocationRegistryService;
use App\Services\SosWorkflowService;
use App\Services\TrackingSessionManager;
use App\Services\VerificationResultEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase2FieldOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_location_prospective_versioning_preserves_historical_rules(): void
    {
        $location = FieldLocation::create([
            'id' => (string) Str::uuid(),
            'name' => 'Kisumu Outlet',
            'latitude' => -0.091702,
            'longitude' => 34.767956,
            'geofence_radius_meters' => 100,
            'county' => 'Kisumu City',
        ]);

        $service = new LocationRegistryService();
        $service->updateGeofenceProspectively($location, -0.092000, 34.768000, 150, 'Geofence expanded for new warehouse area');

        $this->assertEquals(-0.092000, (float) $location->fresh()->latitude);
        $this->assertEquals(150, $location->fresh()->geofence_radius_meters);

        $versions = \DB::table('location_versions')->where('field_location_id', $location->id)->get();
        $this->assertCount(1, $versions);
        $this->assertEquals(1, $versions->first()->version_number);
    }

    public function test_revoked_device_is_blocked_by_middleware(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sales Rep',
            'email' => 'rep@bara.app',
            'password' => bcrypt('password'),
        ]);

        $deviceService = new DeviceLifecycleService();
        $device = $deviceService->registerDevice($user, 'DEV-KEY-999');
        $deviceService->revokeDevice($device);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Device-UUID', 'DEV-KEY-999')
            ->getJson('/api/v1/field-locations');

        $response->assertStatus(403);
        $response->assertJson(['code' => 'DEVICE_REVOKED']);
    }

    public function test_tracking_session_privacy_boundary_blocks_unauthorized_points(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Field Worker',
            'email' => 'worker@bara.app',
            'password' => bcrypt('password'),
        ]);

        $manager = new TrackingSessionManager();
        $session = $manager->startSession($user, purpose: 'shift');
        $manager->stopSession($session);

        $this->expectException(\RuntimeException::class);
        $manager->ingestPoints($session, [
            [
                'latitude' => -1.286389,
                'longitude' => 36.817223,
                'recorded_at' => now()->toIso8601String(),
            ]
        ]);
    }

    public function test_clock_drift_detection_flags_suspicious_timestamps(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Driver',
            'email' => 'driver@bara.app',
            'password' => bcrypt('password'),
        ]);

        $manager = new TrackingSessionManager();
        $session = $manager->startSession($user, purpose: 'shift');

        // Points recorded 10 minutes in the past (suspicious clock drift > 5 minutes)
        $pastPointTime = now()->subMinutes(10)->toIso8601String();

        $result = $manager->ingestPoints($session, [
            [
                'latitude' => -1.286389,
                'longitude' => 36.817223,
                'recorded_at' => $pastPointTime,
            ]
        ]);

        $this->assertEquals(1, $result['ingested_count']);
        $this->assertEquals(1, $result['flagged_drift_count']);
    }

    public function test_sos_emergency_workflow_escalation(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Collector',
            'email' => 'collector@bara.app',
            'password' => bcrypt('password'),
        ]);

        $responder = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Supervisor',
            'email' => 'supervisor@bara.app',
            'password' => bcrypt('password'),
        ]);

        $sosService = new SosWorkflowService();
        $sos = $sosService->triggerSos($user, -1.286389, 36.817223);

        $this->assertEquals('active', $sos->status);

        $sosService->assignResponder($sos, $responder);
        $this->assertEquals('responding', $sos->fresh()->status);
        $this->assertEquals($responder->id, $sos->fresh()->responder_id);

        $sosService->resolveSos($sos, 'Assisted on site by local security.');
        $this->assertEquals('resolved', $sos->fresh()->status);
    }

    public function test_attendance_adapters_unify_into_signed_verification_result(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Inspector',
            'email' => 'inspector@bara.app',
            'password' => bcrypt('password'),
        ]);

        $factory = new AttendanceAdapterFactory(new VerificationResultEngine());

        // Test QR Code Adapter
        $qrAdapter = $factory->make('qr_code');
        $verificationQr = $qrAdapter->verify($user, ['qr_payload' => 'BARA-LOC-NAIROBI-001']);
        $this->assertEquals('passed', $verificationQr->verification_status);
        $this->assertEquals('qr_code', $verificationQr->attendance_adapter);
        $this->assertNotEmpty($verificationQr->signature_hash);

        // Test Face ID Adapter
        $faceAdapter = $factory->make('face_id');
        $verificationFace = $faceAdapter->verify($user, ['face_signature' => 'FACE_HASH_99120']);
        $this->assertEquals('passed', $verificationFace->verification_status);
        $this->assertEquals('face_id', $verificationFace->attendance_adapter);
    }
}
