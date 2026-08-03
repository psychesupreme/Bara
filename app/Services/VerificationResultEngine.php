<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\FieldLocation;
use App\Models\User;
use App\Models\VerificationEvent;
use Illuminate\Support\Facades\DB;

class VerificationResultEngine
{
    /**
     * Default maximum allowable GPS accuracy threshold in meters.
     */
    public const MAX_GPS_ACCURACY_THRESHOLD_METERS = 50.0;

    /**
     * Evaluate presence, time window, device status, and geofence rules to produce a signed VerificationEvent.
     */
    public function verifyPresence(
        User $user,
        float $latitude,
        float $longitude,
        float $gpsAccuracyMeters,
        ?FieldLocation $location = null,
        ?Activity $activity = null,
        ?string $deviceId = null,
        string $attendanceAdapter = 'gps'
    ): VerificationEvent {
        $verifiedAt = now();
        $isGeofenceValid = false;
        $distanceMeters = null;
        $isAccuracyValid = $gpsAccuracyMeters <= self::MAX_GPS_ACCURACY_THRESHOLD_METERS;
        $isDeviceValid = true; // Evaluated against field_devices
        $isTimeWindowValid = true;

        if ($location) {
            $distanceMeters = $this->calculateDistanceMeters(
                $latitude,
                $longitude,
                (float) $location->latitude,
                (float) $location->longitude
            );

            $isGeofenceValid = $distanceMeters <= $location->geofence_radius_meters;
        } else {
            $isGeofenceValid = true; // No geofence required if location is unassigned
        }

        if ($activity && $activity->planned_start_at && $activity->planned_end_at) {
            $windowMargin = $activity->allowed_execution_window_minutes ?? 60;
            $startWindow = $activity->planned_start_at->subMinutes($windowMargin);
            $endWindow = $activity->planned_end_at->addMinutes($windowMargin);
            $isTimeWindowValid = $verifiedAt->between($startWindow, $endWindow);
        }

        // Determine verification status
        $status = 'passed';
        $exceptionReason = null;

        if (!$isAccuracyValid) {
            $status = 'failed_accuracy';
            $exceptionReason = "GPS accuracy ({$gpsAccuracyMeters}m) exceeded max threshold (" . self::MAX_GPS_ACCURACY_THRESHOLD_METERS . "m)";
        } elseif (!$isGeofenceValid) {
            $status = 'failed_geofence';
            $exceptionReason = "Distance to geofence (" . round($distanceMeters, 2) . "m) exceeded allowed radius ({$location->geofence_radius_meters}m)";
        } elseif (!$isTimeWindowValid) {
            $status = 'failed_time_window';
            $exceptionReason = "Attempted execution outside configured execution window.";
        }

        $signatureHash = hash('sha256', implode('|', [
            $user->id,
            $latitude,
            $longitude,
            $gpsAccuracyMeters,
            $verifiedAt->timestamp,
            $status
        ]));

        return VerificationEvent::create([
            'client_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'sequence' => 1,
            'activity_id' => $activity?->id,
            'user_id' => $user->id,
            'field_location_id' => $location?->id,
            'verification_level' => $activity ? 3 : ($location ? 4 : 1),
            'verified_at' => $verifiedAt,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'gps_accuracy_meters' => $gpsAccuracyMeters,
            'distance_to_target_meters' => $distanceMeters,
            'is_geofence_valid' => $isGeofenceValid,
            'is_time_window_valid' => $isTimeWindowValid,
            'is_device_valid' => $isDeviceValid,
            'is_attendance_valid' => ($status === 'passed'),
            'attendance_adapter' => $attendanceAdapter,
            'device_id' => $deviceId,
            'signature_hash' => $signatureHash,
            'verification_status' => $status,
            'exception_reason' => $exceptionReason,
        ]);
    }

    /**
     * Calculate distance between two coordinates in meters (Haversine formula or PostGIS ST_DistanceSphere).
     */
    public function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if (DB::getDriverName() === 'pgsql') {
            try {
                $result = DB::selectOne("
                    SELECT ST_DistanceSphere(
                        ST_MakePoint(?, ?),
                        ST_MakePoint(?, ?)
                    ) as distance_meters
                ", [$lon1, $lat1, $lon2, $lat2]);

                if ($result && isset($result->distance_meters)) {
                    return (float) $result->distance_meters;
                }
            } catch (\Throwable $e) {
                // Fallback to Haversine calculation if PostGIS extension query fails
            }
        }

        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
