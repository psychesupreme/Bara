<?php

namespace App\Services;

use App\Models\FieldDevice;
use App\Models\TrackingPoint;
use App\Models\TrackingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TrackingSessionManager
{
    /**
     * Max allowed clock drift between device recorded_at and server received_at in seconds.
     */
    public const MAX_CLOCK_DRIFT_SECONDS = 300; // 5 minutes

    /**
     * Start a shift-bounded location tracking session.
     */
    public function startSession(User $user, ?FieldDevice $device = null, string $purpose = 'shift'): TrackingSession
    {
        // Cease active sessions for the user before starting a new session
        TrackingSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
            ]);

        return TrackingSession::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'user_id' => $user->id,
            'field_device_id' => $device?->id,
            'started_at' => now(),
            'purpose' => $purpose,
            'is_active' => true,
        ]);
    }

    /**
     * Stop tracking session (tracking ceases immediately when shift ends).
     */
    public function stopSession(TrackingSession $session): TrackingSession
    {
        $session->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        return $session;
    }

    /**
     * Ingest tracking points stream into active session with clock drift detection.
     */
    public function ingestPoints(TrackingSession $session, array $points): array
    {
        if (!$session->is_active) {
            throw new \RuntimeException("Cannot ingest tracking points for an inactive/ended tracking session.");
        }

        $receivedAt = now();
        $ingestedCount = 0;
        $flaggedDriftCount = 0;

        foreach ($points as $point) {
            $recordedAt = Carbon::parse($point['recorded_at']);
            $clockDriftSeconds = abs($receivedAt->diffInSeconds($recordedAt));
            $isSuspiciousDrift = $clockDriftSeconds > self::MAX_CLOCK_DRIFT_SECONDS;

            if ($isSuspiciousDrift) {
                $flaggedDriftCount++;
            }

            TrackingPoint::create([
                'client_uuid' => $point['client_uuid'] ?? (string) Str::uuid(),
                'sequence' => $point['sequence'] ?? 1,
                'session_id' => $session->id,
                'latitude' => (float) $point['latitude'],
                'longitude' => (float) $point['longitude'],
                'accuracy_meters' => (float) ($point['accuracy_meters'] ?? 0.0),
                'speed_mps' => isset($point['speed_mps']) ? (float) $point['speed_mps'] : null,
                'heading_degrees' => isset($point['heading_degrees']) ? (float) $point['heading_degrees'] : null,
                'recorded_at' => $recordedAt,
                'received_at' => $receivedAt,
                'is_mock_location' => (bool) ($point['is_mock_location'] ?? false) || $isSuspiciousDrift,
            ]);

            $ingestedCount++;
        }

        return [
            'ingested_count' => $ingestedCount,
            'flagged_drift_count' => $flaggedDriftCount,
        ];
    }
}
