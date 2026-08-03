<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Str;

class FollowUpAutomationService
{
    /**
     * Automatically schedule a follow-up activity if survey score fails or follow-up outcome is flagged.
     */
    public function evaluateAndScheduleFollowUp(Activity $activity, ?float $surveyScore = null): ?Activity
    {
        $shouldSchedule = false;
        $title = "Follow-up: " . $activity->title;

        if ($surveyScore !== null && $surveyScore < 70.0) {
            $shouldSchedule = true;
            $title = "Follow-up (Failed Inspection {$surveyScore}%): " . $activity->title;
        } elseif (in_array($activity->outcome_code, ['PROMISE_TO_PAY', 'REINSPECTION_REQUIRED', 'MSL_NON_COMPLIANT'], true)) {
            $shouldSchedule = true;
            $title = "Follow-up ({$activity->outcome_code}): " . $activity->title;
        }

        if (!$shouldSchedule) {
            return null;
        }

        $nextDate = now()->addDays(2);

        return Activity::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'reference_no' => $activity->reference_no . '-FOL-' . Str::random(4),
            'activity_type' => 'task',
            'category' => 'follow_up',
            'title' => $title,
            'description' => "Automated follow-up activity triggered from parent activity {$activity->reference_no}",
            'customer_id' => $activity->customer_id,
            'field_location_id' => $activity->field_location_id,
            'parent_activity_id' => $activity->id,
            'planned_start_at' => $nextDate,
            'planned_end_at' => $nextDate->copy()->addMinutes(60),
            'due_at' => $nextDate->copy()->addMinutes(120),
            'priority' => 'high',
            'status' => 'pending',
            'approval_policy' => 'auto',
        ]);
    }
}
