<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LeaveManagementService
{
    /**
     * Submit leave request after checking leave balance.
     */
    public function submitLeaveRequest(
        User $user,
        string $leaveType,
        Carbon $startDate,
        Carbon $endDate,
        ?string $reason = null
    ): LeaveRequest {
        $startOfDay = $startDate->copy()->startOfDay();
        $endOfDay = $endDate->copy()->startOfDay();
        $totalDays = max(1.0, (float) ($startOfDay->diffInDays($endOfDay) + 1));

        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type', $leaveType)
            ->first();

        if (!$balance) {
            $balance = LeaveBalance::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'leave_type' => $leaveType,
                'balance_days' => 21.0,
                'accrued_days' => 0.0,
                'allow_negative_balance' => false,
            ]);
        }

        // Check if submission exceeds available balance (Rule 164)
        if ($balance->balance_days < $totalDays && !$balance->allow_negative_balance) {
            throw new InvalidArgumentException("Insufficient leave balance ({$balance->balance_days} days available, {$totalDays} requested) and negative balance is disabled (Rule 164).");
        }

        return LeaveRequest::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'user_id' => $user->id,
            'leave_type' => $leaveType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    /**
     * Approve leave request and deduct from user leave balance.
     */
    public function approveLeave(LeaveRequest $request, User $reviewer, ?string $notes = null): LeaveRequest
    {
        $request->update([
            'status' => 'approved',
            'reviewer_id' => $reviewer->id,
            'review_notes' => $notes,
        ]);

        $balance = LeaveBalance::where('user_id', $request->user_id)
            ->where('leave_type', $request->leave_type)
            ->first();

        if ($balance) {
            $balance->decrement('balance_days', $request->total_days);
        }

        return $request;
    }
}
