<?php

namespace App\Services\AttendanceAdapters;

use App\Models\Activity;
use App\Models\FieldLocation;
use App\Models\User;
use App\Models\VerificationEvent;

interface AttendanceAdapterInterface
{
    /**
     * Verify user attendance and return a signed VerificationEvent.
     */
    public function verify(
        User $user,
        array $params,
        ?FieldLocation $location = null,
        ?Activity $activity = null
    ): VerificationEvent;
}
