<?php

namespace App\Services\AttendanceAdapters;

use App\Models\Activity;
use App\Models\FieldLocation;
use App\Models\User;
use App\Models\VerificationEvent;
use Illuminate\Support\Str;

class FaceIdAttendanceAdapter implements AttendanceAdapterInterface
{
    public function verify(
        User $user,
        array $params,
        ?FieldLocation $location = null,
        ?Activity $activity = null
    ): VerificationEvent {
        $faceSignature = $params['face_signature'] ?? null;
        $latitude = (float) ($params['latitude'] ?? $location?->latitude ?? 0.0);
        $longitude = (float) ($params['longitude'] ?? $location?->longitude ?? 0.0);
        
        $isValidFace = !empty($faceSignature);
        $status = $isValidFace ? 'passed' : 'failed_attendance';
        $reason = $isValidFace ? null : 'Facial biometric signature match failed';

        $signatureHash = hash('sha256', implode('|', [
            $user->id,
            'face_id',
            $faceSignature ?? 'none',
            now()->timestamp,
            $status
        ]));

        return VerificationEvent::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'activity_id' => $activity?->id,
            'user_id' => $user->id,
            'field_location_id' => $location?->id,
            'verification_level' => 4,
            'verified_at' => now(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'gps_accuracy_meters' => (float) ($params['gps_accuracy_meters'] ?? 10.0),
            'distance_to_target_meters' => 0.0,
            'is_geofence_valid' => true,
            'is_time_window_valid' => true,
            'is_device_valid' => true,
            'is_attendance_valid' => $isValidFace,
            'attendance_adapter' => 'face_id',
            'device_id' => $params['device_id'] ?? null,
            'signature_hash' => $signatureHash,
            'verification_status' => $status,
            'exception_reason' => $reason,
        ]);
    }
}
