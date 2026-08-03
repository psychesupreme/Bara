<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Collection;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CollectionService;
use App\Services\FollowUpAutomationService;
use App\Services\MpesaStkAdapter;
use App\Services\PaymentProcessingService;
use App\Services\PromiseToPayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class Phase5CollectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_collection_capture_allocates_invoice_balances(): void
    {
        $collector = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Collector 1',
            'email' => 'collector1@bara.app',
            'password' => bcrypt('password'),
        ]);

        $customerId = (string) Str::uuid();

        $invoice = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-2026-001',
            'customer_id' => $customerId,
            'total_amount' => 10000.00,
            'paid_amount' => 0.00,
            'balance_amount' => 10000.00,
            'currency' => 'KES',
            'status' => 'unpaid',
            'due_date' => now()->addDays(30),
        ]);

        $service = new CollectionService(new PaymentProcessingService());
        $collection = $service->captureCollection(
            collector: $collector,
            customerId: $customerId,
            amount: 4000.00,
            paymentMode: 'cash',
            currency: 'KES',
            invoiceAllocations: [
                ['invoice_id' => $invoice->id, 'allocated_amount' => 4000.00]
            ]
        );

        $this->assertEquals('confirmed', $collection->status);
        $this->assertEquals('partial', $invoice->fresh()->status);
        $this->assertEquals(6000.00, $invoice->fresh()->balance_amount);
    }

    public function test_mpesa_stk_push_initiates_and_processes_callback(): void
    {
        $adapter = new MpesaStkAdapter();
        $stkResponse = $adapter->initiateStkPush('0712345678', 1500.00, 'ACC-991');

        $this->assertTrue($stkResponse['success']);
        $this->assertNotEmpty($stkResponse['checkout_request_id']);

        $callbackData = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'CheckoutRequestID' => $stkResponse['checkout_request_id'],
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QEK8921L']
                        ]
                    ]
                ]
            ]
        ];

        $callbackResult = $adapter->handleCallback($callbackData);
        $this->assertTrue($callbackResult['is_successful']);
        $this->assertEquals('QEK8921L', $callbackResult['mpesa_receipt_number']);
    }

    public function test_segregation_of_duties_blocks_collector_from_reconciling_own_payment(): void
    {
        $collector = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Collector 2',
            'email' => 'collector2@bara.app',
            'password' => bcrypt('password'),
        ]);

        $service = new CollectionService(new PaymentProcessingService());
        $collection = $service->captureCollection(
            collector: $collector,
            customerId: (string) Str::uuid(),
            amount: 5000.00,
            paymentMode: 'cash'
        );

        // Segregation of Duties violation attempt: Collector trying to reconcile own payment (Rule 75)
        $this->expectException(InvalidArgumentException::class);
        $service->reconcileCollection($collection, $collector, 'Reconciling my own payment');
    }

    public function test_posted_collection_reversal_creates_compensating_entry_without_deletion(): void
    {
        $collector = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Collector 3',
            'email' => 'collector3@bara.app',
            'password' => bcrypt('password'),
        ]);

        $supervisor = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Finance Admin',
            'email' => 'finance@bara.app',
            'password' => bcrypt('password'),
        ]);

        $invoice = Invoice::create([
            'id' => (string) Str::uuid(),
            'invoice_number' => 'INV-2026-002',
            'customer_id' => (string) Str::uuid(),
            'total_amount' => 5000.00,
            'paid_amount' => 0.00,
            'balance_amount' => 5000.00,
            'currency' => 'KES',
            'status' => 'unpaid',
            'due_date' => now(),
        ]);

        $service = new CollectionService(new PaymentProcessingService());
        $collection = $service->captureCollection(
            collector: $collector,
            customerId: $invoice->customer_id,
            amount: 5000.00,
            paymentMode: 'cash',
            invoiceAllocations: [
                ['invoice_id' => $invoice->id, 'allocated_amount' => 5000.00]
            ]
        );

        $this->assertEquals(0.00, $invoice->fresh()->balance_amount);
        $this->assertEquals('paid', $invoice->fresh()->status);

        // Reversing posted collection creates compensating reversal record (Rule 76)
        $reversal = $service->reverseCollection($collection, $supervisor, 'Wrong cheque posting');

        $this->assertEquals('reversed', $collection->fresh()->status);
        $this->assertNotNull($reversal->reversal_receipt_number);
        $this->assertEquals(5000.00, $invoice->fresh()->balance_amount);
        $this->assertEquals('unpaid', $invoice->fresh()->status);
    }

    public function test_offline_mpesa_stk_capture_is_blocked(): void
    {
        $collector = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Collector 4',
            'email' => 'collector4@bara.app',
            'password' => bcrypt('password'),
        ]);

        $service = new CollectionService(new PaymentProcessingService());

        // Offline capture for M-Pesa STK must be blocked (Rule 80)
        $this->expectException(InvalidArgumentException::class);
        $service->captureCollection(
            collector: $collector,
            customerId: (string) Str::uuid(),
            amount: 2000.00,
            paymentMode: 'mpesa_stk',
            isOfflineCaptured: true
        );
    }

    public function test_promise_to_pay_triggers_automated_followup_activity(): void
    {
        $activity = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'COL-ACT-001',
            'activity_type' => 'collection',
            'title' => 'Customer Debt Collection Visit',
            'status' => 'completed',
        ]);

        $promiseService = new PromiseToPayService(new FollowUpAutomationService());
        $promise = $promiseService->recordPromise(
            customerId: (string) Str::uuid(),
            promisedAmount: 15000.00,
            promisedDate: Carbon::parse('2026-08-10'),
            activity: $activity,
            notes: 'Customer promised payment by end of week'
        );

        $this->assertEquals('pending', $promise->status);
        $this->assertEquals('PROMISE_TO_PAY', $activity->fresh()->outcome_code);

        $followUp = Activity::where('parent_activity_id', $activity->id)->first();
        $this->assertNotNull($followUp);
        $this->assertStringContainsString('PROMISE_TO_PAY', $followUp->title);
    }
}
