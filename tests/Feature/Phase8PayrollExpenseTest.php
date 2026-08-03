<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\CompensationProfile;
use App\Models\ExpensePolicy;

use App\Models\Timesheet;
use App\Models\User;
use App\Services\AssetLifecycleService;
use App\Services\ExpenseManagementService;
use App\Services\KraPayeTaxAdapter;
use App\Services\PayrollEngineService;
use App\Services\TimesheetEngineService;
use App\Services\OvertimeCalculationEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class Phase8PayrollExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_payroll_engine_calculates_wages_and_kra_paye_deductions(): void
    {
        $initiator = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'HR Admin',
            'email' => 'hradmin@bara.app',
            'password' => bcrypt('password'),
        ]);

        $worker = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Field Worker 1',
            'email' => 'worker1@bara.app',
            'password' => bcrypt('password'),
        ]);

        CompensationProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $worker->id,
            'pay_rate_type' => 'monthly',
            'base_rate' => 50000.00,
        ]);

        $timesheetService = new TimesheetEngineService(new OvertimeCalculationEngine());
        $timesheet = $timesheetService->recordShiftTimesheet(
            user: $worker,
            clockIn: Carbon::parse('2026-07-01 08:00:00'),
            clockOut: Carbon::parse('2026-07-01 17:00:00')
        );
        $timesheetService->approveTimesheet($timesheet, $initiator);

        $payrollService = new PayrollEngineService(new KraPayeTaxAdapter());
        $payRun = $payrollService->calculatePayRun(
            initiator: $initiator,
            periodStart: Carbon::parse('2026-07-01'),
            periodEnd: Carbon::parse('2026-07-31')
        );

        $this->assertEquals('calculated', $payRun->status);
        $this->assertGreaterThan(0.0, $payRun->total_gross_pay);
        $this->assertGreaterThan(0.0, $payRun->total_statutory_deductions);
    }

    public function test_pay_run_review_locks_underlying_timesheets_and_expenses(): void
    {
        $initiator = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'HR Admin 2',
            'email' => 'hradmin2@bara.app',
            'password' => bcrypt('password'),
        ]);

        $worker = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Field Worker 2',
            'email' => 'worker2@bara.app',
            'password' => bcrypt('password'),
        ]);

        $timesheet = Timesheet::create([
            'id' => (string) Str::uuid(),
            'user_id' => $worker->id,
            'date' => '2026-07-15',
            'regular_hours' => 8.0,
            'status' => 'approved',
            'is_locked' => false,
        ]);

        $payrollService = new PayrollEngineService(new KraPayeTaxAdapter());
        $payRun = $payrollService->calculatePayRun(
            initiator: $initiator,
            periodStart: Carbon::parse('2026-07-01'),
            periodEnd: Carbon::parse('2026-07-31')
        );

        $payrollService->reviewPayRun($payRun);

        $this->assertEquals('reviewed', $payRun->fresh()->status);
        $this->assertTrue($timesheet->fresh()->is_locked);
    }

    public function test_segregation_of_duties_blocks_pay_run_initiator_from_approving(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Finance Controller',
            'email' => 'finance@bara.app',
            'password' => bcrypt('password'),
        ]);

        $payrollService = new PayrollEngineService(new KraPayeTaxAdapter());
        $payRun = $payrollService->calculatePayRun(
            initiator: $user,
            periodStart: Carbon::parse('2026-07-01'),
            periodEnd: Carbon::parse('2026-07-31')
        );

        $payrollService->reviewPayRun($payRun);

        // Segregation of Duties violation attempt: Initiator trying to approve pay run (Rule 181)
        $this->expectException(InvalidArgumentException::class);
        $payrollService->approvePayRun($payRun, $user);
    }

    public function test_expense_policy_enforcement_blocks_cap_exceeded_claims(): void
    {
        $worker = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Field Worker 3',
            'email' => 'worker3@bara.app',
            'password' => bcrypt('password'),
        ]);

        ExpensePolicy::create([
            'id' => (string) Str::uuid(),
            'category' => 'meals',
            'max_claim_amount' => 2000.00, // Max KES 2,000 per claim
            'receipt_required_above' => 500.00,
        ]);

        $service = new ExpenseManagementService();

        // Attempting to claim KES 3,500 (> KES 2,000 cap) must be blocked (Rule 195)
        $this->expectException(InvalidArgumentException::class);
        $service->submitClaim(
            user: $worker,
            category: 'meals',
            merchant: 'Grand Hotel',
            amount: 3500.00,
            receiptUrl: 'https://storage.bara.app/receipts/r1.jpg'
        );
    }

    public function test_asset_assignment_blocks_reassigning_active_in_use_asset(): void
    {
        $worker1 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Worker A',
            'email' => 'workera@bara.app',
            'password' => bcrypt('password'),
        ]);

        $worker2 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Worker B',
            'email' => 'workerb@bara.app',
            'password' => bcrypt('password'),
        ]);

        $asset = Asset::create([
            'id' => (string) Str::uuid(),
            'asset_code' => 'AST-MOB-001',
            'name' => 'Samsung Galaxy Tab A8',
            'asset_type' => 'mobile_device',
            'status' => 'in_inventory',
        ]);

        $service = new AssetLifecycleService();
        $service->assignAsset($asset, $worker1, 'SIG-ACCEPTED-001');

        $this->assertEquals('in_use', $asset->fresh()->status);

        // Attempting to reassign asset currently 'in_use' without return must be blocked (Rule 198)
        $this->expectException(InvalidArgumentException::class);
        $service->assignAsset($asset->fresh(), $worker2, 'SIG-ACCEPTED-002');
    }
}
