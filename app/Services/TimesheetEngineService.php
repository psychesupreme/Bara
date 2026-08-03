<?php

namespace App\Services;

use App\Models\ShiftConfiguration;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TimesheetEngineService
{
    public function __construct(
        protected OvertimeCalculationEngine $overtimeEngine
    ) {}

    /**
     * Auto-generate or update timesheet from clock-in / clock-out shift event.
     */
    public function recordShiftTimesheet(
        User $user,
        Carbon $clockIn,
        Carbon $clockOut,
        ?ShiftConfiguration $shift = null,
        ?float $clockInLat = null,
        ?float $clockInLon = null,
        ?float $clockOutLat = null,
        ?float $clockOutLon = null
    ): Timesheet {
        $dateStr = $clockIn->format('Y-m-d');

        // Check lock state if timesheet already exists
        $existing = Timesheet::where('user_id', $user->id)->whereDate('date', $dateStr)->first();
        if ($existing && $existing->is_locked) {
            throw new InvalidArgumentException("Timesheet for {$dateStr} is locked after approval and cannot be modified (Rule 165).");
        }

        $calculated = $this->overtimeEngine->calculateHours($clockIn, $clockOut);

        $isLate = false;
        $isEarlyDeparture = false;

        if ($shift) {
            $shiftStartTime = Carbon::parse($dateStr . ' ' . $shift->start_time);
            $graceMargin = $shiftStartTime->copy()->addMinutes($shift->grace_period_minutes);
            $isLate = $clockIn->greaterThan($graceMargin);

            $shiftEndTime = Carbon::parse($dateStr . ' ' . $shift->end_time);
            $isEarlyDeparture = $clockOut->lessThan($shiftEndTime);
        }

        if ($existing) {
            $existing->update([
                'sequence' => $existing->sequence + 1,
                'shift_configuration_id' => $shift?->id,
                'clock_in_at' => $clockIn,
                'clock_out_at' => $clockOut,
                'clock_in_latitude' => $clockInLat,
                'clock_in_longitude' => $clockInLon,
                'clock_out_latitude' => $clockOutLat,
                'clock_out_longitude' => $clockOutLon,
                'regular_hours' => $calculated['regular_hours'],
                'overtime_hours' => $calculated['overtime_hours'],
                'holiday_overtime_hours' => $calculated['holiday_overtime_hours'],
                'is_late' => $isLate,
                'is_early_departure' => $isEarlyDeparture,
                'status' => 'pending',
                'is_locked' => false,
            ]);

            return $existing->fresh();
        }

        return Timesheet::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'user_id' => $user->id,
            'date' => $dateStr,
            'shift_configuration_id' => $shift?->id,
            'clock_in_at' => $clockIn,
            'clock_out_at' => $clockOut,
            'clock_in_latitude' => $clockInLat,
            'clock_in_longitude' => $clockInLon,
            'clock_out_latitude' => $clockOutLat,
            'clock_out_longitude' => $clockOutLon,
            'regular_hours' => $calculated['regular_hours'],
            'overtime_hours' => $calculated['overtime_hours'],
            'holiday_overtime_hours' => $calculated['holiday_overtime_hours'],
            'is_late' => $isLate,
            'is_early_departure' => $isEarlyDeparture,
            'status' => 'pending',
            'is_locked' => false,
        ]);
    }

    /**
     * Approve timesheet and lock record from further modifications.
     */
    public function approveTimesheet(Timesheet $timesheet, User $reviewer, ?string $notes = null): Timesheet
    {
        $timesheet->update([
            'status' => 'approved',
            'reviewer_id' => $reviewer->id,
            'review_notes' => $notes,
            'is_locked' => true, // Locked upon approval (Rule 165)
        ]);

        return $timesheet;
    }

    /**
     * Revert approved timesheet back to pending (unlocking it).
     */
    public function unlockTimesheet(Timesheet $timesheet, User $authorizedUser): Timesheet
    {
        $timesheet->update([
            'status' => 'pending',
            'is_locked' => false,
        ]);

        return $timesheet;
    }
}
