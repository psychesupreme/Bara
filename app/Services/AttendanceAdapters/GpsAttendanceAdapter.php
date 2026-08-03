<?php

namespace App\Services\AttendanceAdapters;

use App\Models\Activity;
use App\Models\FieldLocation;
use App\Models\User;
use App\Models\VerificationEvent;
use App\Services\VerificationResultEngine;

class GpsAttendanceAdapter implements AttendanceAdapterInterface
{
    public function __construct(
        protected VerificationResultEngine $verificationEngine
    ) {}

    public function verify(
        User $user,
        array $params,
        ?FieldLocation $location = null,
        ?Activity $activity = null
    ): VerificationEvent {
        return $this->verificationEngine->verifyPresence(
            user: $user,
            latitude: (float) ($params['latitude'] ?? 0.0),
            longitude: (float) ($params['longitude'] ?? 0.0),
            gpsAccuracyMeters: (float) ($params['gps_accuracy_meters'] ?? 999.0),
            location: $location,
            activity: $activity,
            deviceId: $params['device_id'] ?? null,
            attendanceAdapter: 'gps'
        );
    }
}
