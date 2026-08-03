<?php

namespace App\Services;

use App\Models\OvertimeRule;
use App\Models\PublicHoliday;
use Carbon\Carbon;

class OvertimeCalculationEngine
{
    /**
     * Calculate regular, standard overtime, and holiday overtime hours for a worked period.
     */
    public function calculateHours(Carbon $clockIn, Carbon $clockOut, ?string $countryCode = 'KE'): array
    {
        $totalHours = max(0.0, round($clockIn->diffInMinutes($clockOut) / 60.0, 2));
        $dateStr = $clockIn->format('Y-m-d');

        // Check if the shift falls on a public holiday
        $holiday = PublicHoliday::whereDate('holiday_date', $dateStr)
            ->where('country_code', $countryCode)
            ->first();

        $rule = OvertimeRule::first();
        $dailyThreshold = $rule ? (float) $rule->daily_threshold_hours : 8.0;

        if ($holiday) {
            // Entire shift on a public holiday is calculated as holiday overtime (Rule 166)
            return [
                'total_hours' => $totalHours,
                'regular_hours' => 0.0,
                'overtime_hours' => 0.0,
                'holiday_overtime_hours' => $totalHours,
                'is_holiday' => true,
                'multiplier' => (float) $holiday->multiplier,
            ];
        }

        $regularHours = min($totalHours, $dailyThreshold);
        $overtimeHours = max(0.0, $totalHours - $dailyThreshold);

        return [
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'holiday_overtime_hours' => 0.0,
            'is_holiday' => false,
            'multiplier' => $rule ? (float) $rule->standard_multiplier : 1.5,
        ];
    }
}
