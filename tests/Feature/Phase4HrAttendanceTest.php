<?php

namespace Tests\Feature;

use App\Models\LeaveBalance;
use App\Models\OvertimeRule;
use App\Models\PublicHoliday;
use App\Models\ShiftConfiguration;
use App\Models\Timesheet;
use App\Models\User;
use App\Services\LeaveManagementService;
use App\Services\OvertimeCalculationEngine;
use App\Services\TimesheetEngineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class Phase4HrAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_timesheet_auto_generation_from_shift_logs(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Worker 1',
            'email' => 'worker1@bara.app',
            'password' => bcrypt('password'),
        ]);

        $shift = ShiftConfiguration::create([
            'id' => (string) Str::uuid(),
            'name' => 'Standard Morning Shift',
            'shift_type' => 'standard',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'grace_period_minutes' => 15,
        ]);

        $clockIn = Carbon::parse('2026-07-28 08:30:00'); // 30 mins late (> 15 mins grace)
        $clockOut = Carbon::parse('2026-07-28 17:30:00'); // 9 hours total (8h regular + 1h overtime)

        $service = new TimesheetEngineService(new OvertimeCalculationEngine());
        $timesheet = $service->recordShiftTimesheet(
            user: $user,
            clockIn: $clockIn,
            clockOut: $clockOut,
            shift: $shift
        );

        $this->assertTrue($timesheet->is_late);
        $this->assertEquals(8.0, $timesheet->regular_hours);
        $this->assertEquals(1.0, $timesheet->overtime_hours);
        $this->assertEquals('pending', $timesheet->status);
    }

    public function test_overtime_calculation_engine_applies_multipliers_and_holiday_rates(): void
    {
        PublicHoliday::create([
            'id' => (string) Str::uuid(),
            'name' => 'Madaraka Day',
            'holiday_date' => '2026-06-01',
            'country_code' => 'KE',
            'multiplier' => 2.0,
        ]);

        $clockIn = Carbon::parse('2026-06-01 08:00:00');
        $clockOut = Carbon::parse('2026-06-01 16:00:00'); // 8 hours on a public holiday

        $engine = new OvertimeCalculationEngine();
        $result = $engine->calculateHours($clockIn, $clockOut, 'KE');

        $this->assertTrue($result['is_holiday']);
        $this->assertEquals(8.0, $result['holiday_overtime_hours']);
        $this->assertEquals(0.0, $result['regular_hours']);
        $this->assertEquals(2.0, $result['multiplier']);
    }

    public function test_approved_timesheet_locks_from_modification(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Worker 2',
            'email' => 'worker2@bara.app',
            'password' => bcrypt('password'),
        ]);

        $reviewer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'HR Manager',
            'email' => 'hr@bara.app',
            'password' => bcrypt('password'),
        ]);

        $service = new TimesheetEngineService(new OvertimeCalculationEngine());
        $timesheet = $service->recordShiftTimesheet(
            user: $user,
            clockIn: Carbon::parse('2026-07-28 08:00:00'),
            clockOut: Carbon::parse('2026-07-28 17:00:00')
        );

        $service->approveTimesheet($timesheet, $reviewer, 'Timesheet verified against shift log.');

        $this->assertTrue($timesheet->fresh()->is_locked);
        $this->assertEquals('approved', $timesheet->fresh()->status);

        // Attempting to re-record or update locked timesheet must throw exception (Rule 165)
        $this->expectException(InvalidArgumentException::class);
        $service->recordShiftTimesheet(
            user: $user,
            clockIn: Carbon::parse('2026-07-28 08:00:00'),
            clockOut: Carbon::parse('2026-07-28 18:00:00')
        );
    }

    public function test_leave_request_blocks_insufficient_balance_unless_negative_allowed(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Staff 1',
            'email' => 'staff1@bara.app',
            'password' => bcrypt('password'),
        ]);

        LeaveBalance::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'leave_type' => 'annual',
            'balance_days' => 3.0,
            'allow_negative_balance' => false,
        ]);

        $service = new LeaveManagementService();

        // Requesting 5 days when only 3 days available must throw exception (Rule 164)
        $this->expectException(InvalidArgumentException::class);
        $service->submitLeaveRequest(
            user: $user,
            leaveType: 'annual',
            startDate: Carbon::parse('2026-08-01'),
            endDate: Carbon::parse('2026-08-05') // 5 days
        );
    }

    public function test_leave_approval_deducts_balance(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Staff 2',
            'email' => 'staff2@bara.app',
            'password' => bcrypt('password'),
        ]);

        $reviewer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'HR Admin',
            'email' => 'admin@bara.app',
            'password' => bcrypt('password'),
        ]);

        LeaveBalance::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'leave_type' => 'annual',
            'balance_days' => 15.0,
        ]);

        $service = new LeaveManagementService();
        $request = $service->submitLeaveRequest(
            user: $user,
            leaveType: 'annual',
            startDate: Carbon::parse('2026-08-01'),
            endDate: Carbon::parse('2026-08-03') // 3 days
        );

        $service->approveLeave($request, $reviewer, 'Approved annual leave');

        $this->assertEquals('approved', $request->fresh()->status);
        $this->assertEquals(12.0, LeaveBalance::where('user_id', $user->id)->first()->balance_days);
    }
}
