<?php

namespace App\Services\AttendanceAdapters;

use App\Models\Activity;
use App\Models\FieldLocation;
use App\Models\User;
use App\Models\VerificationEvent;
use Illuminate\Support\Str;

class QrCodeAttendanceAdapter implements AttendanceAdapterInterface
{
    public function verify(
        User $user,
        array $params,
        ?FieldLocation $location = null,
        ?Activity $activity = null
    ): VerificationEvent {
        $qrPayload = $params['qr_payload'] ?? '';
        $latitude = (float) ($params['latitude'] ?? $location?->latitude ?? 0.0);
        $longitude = (float) ($params['longitude'] ?? $location?->longitude ?? 0.0);
        
        $isValidQr = !empty($qrPayload) && (Str::contains($qrPayload, 'BARA-LOC-') || Str::contains($qrPayload, $location?->code ?? 'VALID'));
        $status = $isValidQr ? 'passed' : 'failed_attendance';
        $reason = $isValidQr ? null : 'Invalid or expired QR code signature';

        $signatureHash = hash('sha256', implode('|', [
            $user->id,
            'qr_code',
            $qrPayload,
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
            'is_attendance_valid' => $isValidQr,
            'attendance_adapter' => 'qr_code',
            'device_id' => $params['device_id'] ?? null,
            'signature_hash' => $signatureHash,
            'verification_status' => $status,
            'exception_reason' => $reason,
        ]);
    }
}
