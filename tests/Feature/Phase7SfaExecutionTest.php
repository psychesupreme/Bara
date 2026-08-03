<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\CommercialProduct;
use App\Models\Customer;
use App\Models\CustomerOutletExtension;
use App\Models\Promotion;
use App\Models\User;
use App\Services\FollowUpAutomationService;
use App\Services\GuidedSellingService;
use App\Services\KraEtimsAdapter;
use App\Services\MerchandisingExecutionService;
use App\Services\OrderOrchestrationService;
use App\Services\TradePromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class Phase7SfaExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_customer_360_aggregates_unified_context(): void
    {
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Westlands Wholesalers',
            'code' => 'CUST-360',
        ]);

        CustomerOutletExtension::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'payment_terms' => 'credit_30',
            'credit_limit' => 50000.00,
        ]);

        $service = new GuidedSellingService();
        $profile = $service->getCustomer360Profile($customer);

        $this->assertEquals('Westlands Wholesalers', $profile['customer']->name);
        $this->assertEquals(50000.00, $profile['commercial_details']->credit_limit);
        $this->assertEquals(0, $profile['open_orders_count']);
    }

    public function test_order_compliance_gatekeeper_blocks_orders_exceeding_credit_limit(): void
    {
        $rep = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sales Rep 2',
            'email' => 'rep2@bara.app',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Small Retailer B',
            'code' => 'CUST-CREDIT',
        ]);

        CustomerOutletExtension::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'credit_limit' => 10000.00, // $10,000 credit limit
        ]);

        $product = CommercialProduct::create([
            'id' => (string) Str::uuid(),
            'name' => 'Juice Box 1L',
            'sku' => 'JB-1000',
        ]);

        $service = new OrderOrchestrationService(new KraEtimsAdapter());
        $order = $service->createOrder(
            salesRep: $rep,
            customer: $customer,
            lines: [
                ['product_id' => $product->id, 'quantity' => 100, 'unit_price' => 200.00] // Total: $20,000 > $10,000 credit limit
            ]
        );

        $service->transitionStatus($order, 'pending_approval');

        // Approval attempt must be blocked by compliance gatekeeper (OM-002)
        $this->expectException(InvalidArgumentException::class);
        $service->transitionStatus($order, 'approved');
    }

    public function test_kra_etims_adapter_generates_receipt_payload_and_qr_code(): void
    {
        $rep = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sales Rep 3',
            'email' => 'rep3@bara.app',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mombasa Supermarket',
            'code' => 'CUST-MBA',
        ]);

        $product = CommercialProduct::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mineral Water 500ml',
            'sku' => 'MW-500',
        ]);

        $service = new OrderOrchestrationService(new KraEtimsAdapter());
        $order = $service->createOrder(
            salesRep: $rep,
            customer: $customer,
            lines: [
                ['product_id' => $product->id, 'quantity' => 50, 'unit_price' => 50.00]
            ]
        );

        $service->transitionStatus($order, 'pending_approval');
        $service->transitionStatus($order, 'approved');
        $service->transitionStatus($order, 'allocated');
        $dispatched = $service->transitionStatus($order, 'dispatched');

        $this->assertNotNull($dispatched->etims_receipt_number);
        $this->assertNotNull($dispatched->etims_signature);
        $this->assertStringContainsString('https://etims.kra.go.ke/verify', $dispatched->etims_qr_code);
    }

    public function test_trade_promotion_engine_auto_applies_buy_x_get_y_deal(): void
    {
        $rep = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sales Rep 4',
            'email' => 'rep4@bara.app',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Promo Customer',
            'code' => 'CUST-PROMO',
        ]);

        $product = CommercialProduct::create([
            'id' => (string) Str::uuid(),
            'name' => 'Energy Drink 250ml',
            'sku' => 'ED-250',
        ]);

        // Buy 10, Get 2 Free Promo
        Promotion::create([
            'id' => (string) Str::uuid(),
            'name' => 'Buy 10 Get 2 Free',
            'code' => 'PROMO-ED-10',
            'promo_type' => 'buy_x_get_y',
            'buy_product_id' => $product->id,
            'buy_quantity' => 10,
            'get_product_id' => $product->id,
            'get_quantity' => 2,
            'effective_from' => now()->subDays(5),
            'is_active' => true,
        ]);

        $orderService = new OrderOrchestrationService(new KraEtimsAdapter());
        $order = $orderService->createOrder(
            salesRep: $rep,
            customer: $customer,
            lines: [
                ['product_id' => $product->id, 'quantity' => 20, 'unit_price' => 100.00]
            ]
        );

        $promoService = new TradePromotionService();
        $updatedOrder = $promoService->evaluateAndApplyPromotions($order);

        $this->assertDatabaseHas('promotion_claims', [
            'sales_order_id' => $order->id,
            'status' => 'approved',
        ]);
    }

    public function test_merchandising_low_msl_score_triggers_automated_corrective_activity(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Merchandiser 1',
            'email' => 'merch1@bara.app',
            'password' => bcrypt('password'),
        ]);

        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'name' => 'Kisumu Outlet',
            'code' => 'CUST-KSM',
        ]);

        $p1 = CommercialProduct::create(['id' => (string) Str::uuid(), 'name' => 'Item A', 'sku' => 'SKU-A']);
        $p2 = CommercialProduct::create(['id' => (string) Str::uuid(), 'name' => 'Item B', 'sku' => 'SKU-B']);
        $p3 = CommercialProduct::create(['id' => (string) Str::uuid(), 'name' => 'Item C', 'sku' => 'SKU-C']);

        $activity = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'MRC-ACT-001',
            'activity_type' => 'merchandising',
            'title' => 'Routine Merchandising Check',
            'status' => 'completed',
        ]);

        $service = new MerchandisingExecutionService(new FollowUpAutomationService());
        
        // Out of 3 items, only 1 is in stock -> MSL score = 33.33% (< 70%)
        $observation = $service->recordObservation(
            user: $user,
            customer: $customer,
            productObservations: [
                ['product_id' => $p1->id, 'is_in_stock' => true, 'facing_count' => 2],
                ['product_id' => $p2->id, 'is_in_stock' => false, 'facing_count' => 0],
                ['product_id' => $p3->id, 'is_in_stock' => false, 'facing_count' => 0],
            ],
            activity: $activity
        );

        $this->assertEquals(33.33, $observation->msl_compliance_score);
        $this->assertEquals('MSL_NON_COMPLIANT', $activity->fresh()->outcome_code);

        // Corrective follow-up activity automatically created
        $followUp = Activity::where('parent_activity_id', $activity->id)->first();
        $this->assertNotNull($followUp);
        $this->assertStringContainsString('MSL_NON_COMPLIANT', $followUp->title);
    }
}
