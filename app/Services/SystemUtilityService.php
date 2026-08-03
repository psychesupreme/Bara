<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\LeaveRequest;
use App\Models\RoutePlan;
use App\Models\ShiftConfiguration;
use App\Models\SystemNotice;
use App\Models\User;
use App\Models\UserNoticeAcknowledgment;
use Carbon\Carbon;

class SystemUtilityService
{
    /**
     * Aggregate authorized shift schedules, activity assignments, leave requests, and route plans into a single unified calendar array (CU-001).
     */
    public function getUnifiedCalendar(User $user, Carbon $start, Carbon $end): array
    {
        $activities = Activity::where(function ($q) use ($user) {
            $q->whereHas('assignments', function ($a) use ($user) {
                $a->where('user_id', $user->id);
            });
        })->whereBetween('planned_start_at', [$start, $end])->get();

        $leaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get();

        $routes = RoutePlan::where('sales_rep_id', $user->id)
            ->where('is_active', true)
            ->with('stops.customer')
            ->get();

        $shifts = ShiftConfiguration::where('is_active', true)->get();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'activities' => $activities,
            'leave_requests' => $leaves,
            'route_plans' => $routes,
            'shift_schedules' => $shifts,
        ];
    }

    /**
     * Broadcast targeted system notice.
     */
    public function broadcastNotice(string $title, string $message, ?string $targetRole = null, bool $isMandatory = false): SystemNotice
    {
        return SystemNotice::create([
            'title' => $title,
            'message' => $message,
            'target_role' => $targetRole,
            'is_mandatory' => $isMandatory,
        ]);
    }

    /**
     * Acknowledge mandatory notice.
     */
    public function acknowledgeNotice(SystemNotice $notice, User $user): UserNoticeAcknowledgment
    {
        return UserNoticeAcknowledgment::firstOrCreate(
            [
                'system_notice_id' => $notice->id,
                'user_id' => $user->id,
            ],
            [
                'acknowledged_at' => now(),
            ]
        );
    }
}
