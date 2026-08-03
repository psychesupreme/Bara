<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityRecurrence;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ActivityRecurrenceService
{
    /**
     * Generate next recurring instance for a parent activity without mutating historical records.
     */
    public function generateNextInstance(ActivityRecurrence $recurrence): ?Activity
    {
        $parent = $recurrence->parentActivity;
        if (!$parent) {
            return null;
        }

        $lastDate = $recurrence->last_generated_at 
            ? Carbon::parse($recurrence->last_generated_at) 
            : Carbon::parse($parent->planned_start_at ?? now());

        $nextDate = match ($recurrence->recurrence_pattern) {
            'daily' => $lastDate->addDays($recurrence->interval),
            'weekly' => $lastDate->addWeeks($recurrence->interval),
            'monthly' => $lastDate->addMonths($recurrence->interval),
            default => $lastDate->addDays(1),
        };

        if ($recurrence->end_date && $nextDate->greaterThan(Carbon::parse($recurrence->end_date))) {
            return null;
        }

        // Clone base activity attributes into a new child instance
        $child = Activity::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'reference_no' => $parent->reference_no . '-REC-' . Str::random(4),
            'activity_type' => $parent->activity_type,
            'category' => $parent->category,
            'title' => $parent->title . ' (Recurring)',
            'description' => $parent->description,
            'customer_id' => $parent->customer_id,
            'field_location_id' => $parent->field_location_id,
            'parent_activity_id' => $parent->id,
            'planned_start_at' => $nextDate,
            'planned_end_at' => $nextDate->copy()->addMinutes(60),
            'due_at' => $nextDate->copy()->addMinutes(120),
            'priority' => $parent->priority,
            'status' => 'pending',
            'location_policy' => $parent->location_policy,
            'attendance_policy' => $parent->attendance_policy,
            'evidence_policy' => $parent->evidence_policy,
            'approval_policy' => $parent->approval_policy,
        ]);

        $recurrence->update([
            'last_generated_at' => now(),
        ]);

        return $child;
    }
}
