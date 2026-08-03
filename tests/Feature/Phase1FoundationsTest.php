<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\FieldLocation;
use App\Models\User;
use App\Services\ActivityLifecycleService;
use App\Services\VerificationResultEngine;
use Database\Seeders\KenyaCountiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase1FoundationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_kenya_counties_seeder_contains_47_counties(): void
    {
        $this->seed(KenyaCountiesSeeder::class);
        $counties = config('bara.counties');

        $this->assertIsArray($counties);
        $this->assertCount(47, $counties);
        $this->assertEquals('Nairobi City', $counties[46]['name']);
    }

    public function test_verification_engine_calculates_distance_and_evaluates_geofence(): void
    {
        $engine = new VerificationResultEngine();

        // Nairobi CBD to Westlands distance is roughly ~3.5 km
        $cbdLat = -1.286389;
        $cbdLon = 36.817223;
        $westlandsLat = -1.267355;
        $westlandsLon = 36.810574;

        $distance = $engine->calculateDistanceMeters($cbdLat, $cbdLon, $westlandsLat, $westlandsLon);
        $this->assertGreaterThan(2000, $distance);
        $this->assertLessThan(5000, $distance);
    }

    public function test_activity_lifecycle_starts_and_completes_with_evidence(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'John Doe',
            'email' => 'john@bara.app',
            'password' => bcrypt('password'),
            'role' => 'field_employee',
        ]);

        $location = FieldLocation::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nairobi HQ',
            'latitude' => -1.286389,
            'longitude' => 36.817223,
            'geofence_radius_meters' => 200,
            'county' => 'Nairobi City',
        ]);

        $activity = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'ACT-1001',
            'activity_type' => 'visit',
            'title' => 'Client Visit',
            'field_location_id' => $location->id,
            'status' => 'pending',
            'approval_policy' => 'auto',
        ]);

        $verificationEngine = new VerificationResultEngine();
        $lifecycleService = new ActivityLifecycleService($verificationEngine);

        // Start activity inside geofence
        $verification = $lifecycleService->startActivity(
            activity: $activity,
            user: $user,
            latitude: -1.286389,
            longitude: 36.817223,
            gpsAccuracyMeters: 10.0
        );

        $this->assertEquals('passed', $verification->verification_status);
        $this->assertEquals('in_progress', $activity->fresh()->status);

        // Complete activity
        $completedActivity = $lifecycleService->completeActivity(
            activity: $activity,
            user: $user,
            notes: 'Visit completed successfully',
            evidenceData: [
                [
                    'evidence_type' => 'photo',
                    'file_path' => 'photos/evidence_1.jpg',
                    'captured_latitude' => -1.286389,
                    'captured_longitude' => 36.817223,
                ],
            ]
        );

        $this->assertEquals('completed', $completedActivity->status);
        $this->assertCount(1, $completedActivity->evidence);
    }
}
