<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\CommercialProduct;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NairobiPilotSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $rep1;
    protected User $supervisor;
    protected User $collector;
    protected Customer $naivas;
    protected Customer $sarit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
        $this->artisan('db:seed', ['--class' => 'NairobiPilotSeeder']);

        $this->rep1 = User::where('email', 'nairobi.rep1@bara.app')->firstOrFail();
        $this->supervisor = User::where('email', 'nairobi.supervisor@bara.app')->firstOrFail();
        $this->collector = User::where('email', 'nairobi.collector@bara.app')->firstOrFail();
        
        $this->naivas = Customer::where('code', 'CUST-NAI-001')->firstOrFail();
        $this->sarit = Customer::where('code', 'CUST-NAI-002')->firstOrFail();
    }

    public function test_sanctum_authentication_and_user_context(): void
    {
        $response = $this->actingAs($this->rep1, 'sanctum')
            ->getJson('/api/v1/user');

        $response->assertStatus(200)
            ->assertJsonPath('email', 'nairobi.rep1@bara.app')
            ->assertJsonPath('role', 'rep');
    }

    public function test_customer_360_profile_aggregation(): void
    {
        $response = $this->actingAs($this->rep1, 'sanctum')
            ->getJson("/api/v1/sfa/customer-360/{$this->naivas->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.name', 'Naivas Supermarket CBD Branch')
            ->assertJsonPath('data.commercial_details.credit_limit', 500000);
    }

    public function test_order_creation_and_trade_promotion_auto_application(): void
    {
        $product = CommercialProduct::where('sku', 'SFJ-1000')->firstOrFail();

        $response = $this->actingAs($this->rep1, 'sanctum')
            ->postJson('/api/v1/sfa/orders/create', [
                'customer_id' => $this->sarit->id,
                'lines' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 10,
                        'unit_price' => 150.00,
                    ]
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total_amount', 1500);
    }

    public function test_collection_capture_against_naivas_invoice(): void
    {
        $response = $this->actingAs($this->collector, 'sanctum')
            ->postJson('/api/v1/collections/capture', [
                'customer_id' => $this->naivas->id,
                'amount' => 25000.00,
                'payment_mode' => 'cash',
                'currency' => 'KES',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.amount', 25000);
    }

    public function test_offline_isar_sync_push_logs_and_pull_deltas(): void
    {
        // Test Push-Logs
        $pushResponse = $this->actingAs($this->rep1, 'sanctum')
            ->postJson('/api/v1/sync/push-logs', [
                'logs' => [
                    [
                        'client_uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'sequence' => 1,
                        'entity_type' => 'activity',
                        'payload' => [
                            'reference_no' => 'ACT-SYNC-001',
                            'activity_type' => 'visit',
                            'title' => 'Nairobi CBD Store Check',
                            'status' => 'completed',
                        ]
                    ]
                ]
            ]);

        $pushResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'processed_uuids');

        // Test Pull-Deltas
        $pullResponse = $this->actingAs($this->rep1, 'sanctum')
            ->getJson('/api/v1/sync/pull-deltas?last_synced_sequence=0');

        $pullResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_unified_calendar_for_nairobi_sales_rep(): void
    {
        $response = $this->actingAs($this->rep1, 'sanctum')
            ->getJson('/api/v1/utilities/calendar?start=2026-07-01&end=2026-07-31');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.name', 'Central Field Rep (CBD)');
    }
}
