<?php

namespace Tests\Feature;

use App\Events\ActivityAssignedEvent;
use App\Events\SosTriggeredEvent;
use App\Events\TrackingPointStreamEvent;
use App\Models\Activity;
use App\Models\SosRequest;
use App\Models\TrackingPoint;
use App\Models\TrackingSession;
use App\Models\User;
use App\Services\BackupRestoreService;
use App\Services\DataImporterService;
use App\Services\SystemUtilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase9SystemUtilitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_unified_calendar_aggregates_shifts_activities_and_leaves(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Calendar User',
            'email' => 'cal@bara.app',
            'password' => bcrypt('password'),
        ]);

        $service = new SystemUtilityService();
        $calendar = $service->getUnifiedCalendar(
            user: $user,
            start: Carbon::parse('2026-07-01'),
            end: Carbon::parse('2026-07-31')
        );

        $this->assertEquals('Calendar User', $calendar['user']['name']);
        $this->assertIsArray($calendar['activities']->toArray());
    }

    public function test_dry_run_importer_validates_customer_records(): void
    {
        $service = new DataImporterService();
        $job = $service->validateAndImport(
            fileName: 'customers_import.csv',
            entityType: 'customers',
            rows: [
                ['name' => 'Outlet Alpha', 'code' => 'OUT-A', 'tax_number' => 'P12345'],
                ['name' => '', 'code' => 'OUT-B'], // Invalid row: missing name
            ],
            commit: false
        );

        $this->assertEquals('dry_run', $job->status);
        $this->assertEquals(1, $job->valid_rows);
        $this->assertEquals(1, $job->invalid_rows);
        $this->assertNotEmpty($job->errors);
    }

    public function test_backup_restore_service_creates_snapshot_with_sha256_checksum(): void
    {
        $service = new BackupRestoreService();
        $backup = $service->createBackup();

        $this->assertEquals('completed', $backup->status);
        $this->assertNotEmpty($backup->checksum_sha256);
        $this->assertGreaterThan(0, $backup->size_bytes);

        $restored = $service->restoreBackup($backup);
        $this->assertEquals('restored', $restored->status);
    }

    public function test_realtime_websocket_events_broadcast_payloads(): void
    {
        Event::fake();

        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'WS User',
            'email' => 'ws@bara.app',
            'password' => bcrypt('password'),
        ]);

        $sos = SosRequest::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'latitude' => -1.2833300,
            'longitude' => 36.8166700,
            'status' => 'active',
            'triggered_at' => now(),
        ]);

        event(new SosTriggeredEvent($sos));
        Event::assertDispatched(SosTriggeredEvent::class);

        $activity = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'ACT-WS-001',
            'activity_type' => 'task',
            'title' => 'WebSocket Test Activity',
        ]);

        event(new ActivityAssignedEvent($activity, $user->id));
        Event::assertDispatched(ActivityAssignedEvent::class);

        $session = TrackingSession::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $point = TrackingPoint::create([
            'id' => (string) Str::uuid(),
            'session_id' => $session->id,
            'latitude' => -1.2833300,
            'longitude' => 36.8166700,
            'recorded_at' => now(),
            'received_at' => now(),
        ]);

        event(new TrackingPointStreamEvent($point));
        Event::assertDispatched(TrackingPointStreamEvent::class);
    }
}
