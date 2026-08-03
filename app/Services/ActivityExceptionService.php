<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityException;
use App\Models\User;
use Illuminate\Support\Str;

class ActivityExceptionService
{
    /**
     * Route failed presence/evidence attempts to supervisory exception queue.
     */
    public function routeToException(
        Activity $activity,
        User $user,
        string $exceptionType,
        string $reason
    ): ActivityException {
        $activity->update([
            'status' => 'exception',
        ]);

        return ActivityException::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'exception_type' => $exceptionType,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    /**
     * Supervisor approves exception request.
     */
    public function approveException(ActivityException $exception, User $reviewer, string $notes): ActivityException
    {
        $exception->update([
            'reviewer_id' => $reviewer->id,
            'status' => 'approved',
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        $exception->activity->update([
            'status' => 'approved',
        ]);

        return $exception;
    }

    /**
     * Supervisor rejects exception request.
     */
    public function rejectException(ActivityException $exception, User $reviewer, string $notes): ActivityException
    {
        $exception->update([
            'reviewer_id' => $reviewer->id,
            'status' => 'rejected',
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        $exception->activity->update([
            'status' => 'rejected',
        ]);

        return $exception;
    }
}
