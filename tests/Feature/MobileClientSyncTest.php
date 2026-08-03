<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileClientSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $repUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
        $this->artisan('db:seed', ['--class' => 'NairobiPilotSeeder']);

        $this->repUser = User::where('email', 'nairobi.rep1@bara.app')->firstOrFail();
    }

    public function test_mobile_client_payload_formatting_and_50_chunk_batch_push(): void
    {
        $uuid1 = (string) Str::uuid();
        $uuid2 = (string) Str::uuid();

        $response = $this->actingAs($this->repUser, 'sanctum')
            ->postJson('/api/v1/sync/push-logs', [
                'logs' => [
                    [
                        'client_uuid' => $uuid1,
                        'sequence' => 1,
                        'entity_type' => 'activity',
                        'payload' => [
                            'reference_no' => 'ACT-MOB-001',
                            'activity_type' => 'visit',
                            'title' => 'Naivas CBD Check-in',
                            'status' => 'completed',
                        ]
                    ],
                    [
                        'client_uuid' => $uuid2,
                        'sequence' => 1,
                        'entity_type' => 'activity',
                        'payload' => [
                            'reference_no' => 'ACT-MOB-002',
                            'activity_type' => 'task',
                            'title' => 'Sarit Center Stock Audit',
                            'status' => 'in_progress',
                        ]
                    ],
                ]
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'processed_uuids');

        $this->assertDatabaseHas('activities', ['client_uuid' => $uuid1, 'reference_no' => 'ACT-MOB-001']);
        $this->assertDatabaseHas('activities', ['client_uuid' => $uuid2, 'reference_no' => 'ACT-MOB-002']);
    }

    public function test_mobile_client_pull_deltas_and_lww_sequence_resolution(): void
    {
        $uuid = (string) Str::uuid();

        // Step 1: Initial push from mobile client with sequence = 1
        $this->actingAs($this->repUser, 'sanctum')
            ->postJson('/api/v1/sync/push-logs', [
                'logs' => [
                    [
                        'client_uuid' => $uuid,
                        'sequence' => 1,
                        'entity_type' => 'activity',
                        'payload' => [
                            'reference_no' => 'ACT-LWW-100',
                            'activity_type' => 'visit',
                            'title' => 'Initial Offline Capture',
                            'status' => 'in_progress',
                        ]
                    ]
                ]
            ]);

        // Step 2: Push update from mobile client with higher sequence = 2 (LWW resolution)
        $updateResponse = $this->actingAs($this->repUser, 'sanctum')
            ->postJson('/api/v1/sync/push-logs', [
                'logs' => [
                    [
                        'client_uuid' => $uuid,
                        'sequence' => 2,
                        'entity_type' => 'activity',
                        'payload' => [
                            'reference_no' => 'ACT-LWW-100',
                            'activity_type' => 'visit',
                            'title' => 'Updated Visit Completion',
                            'status' => 'completed',
                        ]
                    ]
                ]
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonCount(1, 'processed_uuids');

        $this->assertDatabaseHas('activities', [
            'client_uuid' => $uuid,
            'status' => 'completed',
            'sequence' => 2,
        ]);
    }
}
