<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityEvent;
use App\Models\ActivityEvidence;
use App\Models\User;
use App\Models\VerificationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityLifecycleService
{
    public function __construct(
        protected VerificationResultEngine $verificationEngine
    ) {}

    /**
     * Start an activity with presence verification.
     */
    public function startActivity(
        Activity $activity,
        User $user,
        float $latitude,
        float $longitude,
        float $gpsAccuracyMeters,
        ?string $deviceId = null
    ): VerificationEvent {
        $verification = $this->verificationEngine->verifyPresence(
            user: $user,
            latitude: $latitude,
            longitude: $longitude,
            gpsAccuracyMeters: $gpsAccuracyMeters,
            location: $activity->fieldLocation,
            activity: $activity,
            deviceId: $deviceId
        );

        if ($verification->verification_status !== 'passed') {
            $this->recordEvent($activity, $user, $activity->status, 'exception', $verification->exception_reason);
            $activity->update([
                'status' => 'exception',
                'actual_start_at' => now(),
                'start_latitude' => $latitude,
                'start_longitude' => $longitude,
                'device_id' => $deviceId,
            ]);

            return $verification;
        }

        $fromStatus = $activity->status;
        $activity->update([
            'status' => 'in_progress',
            'actual_start_at' => now(),
            'start_latitude' => $latitude,
            'start_longitude' => $longitude,
            'device_id' => $deviceId,
        ]);

        $this->recordEvent($activity, $user, $fromStatus, 'in_progress', 'Started activity with verified presence');

        return $verification;
    }

    /**
     * Complete an activity with evidence submission.
     */
    public function completeActivity(
        Activity $activity,
        User $user,
        ?string $notes = null,
        ?array $evidenceData = null,
        ?array $payload = null
    ): Activity {
        return DB::transaction(function () use ($activity, $user, $notes, $evidenceData, $payload) {
            $fromStatus = $activity->status;

            if (!empty($evidenceData)) {
                foreach ($evidenceData as $item) {
                    ActivityEvidence::create([
                        'client_uuid' => $item['client_uuid'] ?? (string) Str::uuid(),
                        'sequence' => $item['sequence'] ?? 1,
                        'activity_id' => $activity->id,
                        'evidence_type' => $item['evidence_type'] ?? 'photo',
                        'file_path' => $item['file_path'] ?? null,
                        'mime_type' => $item['mime_type'] ?? null,
                        'file_size_bytes' => $item['file_size_bytes'] ?? null,
                        'captured_latitude' => $item['captured_latitude'] ?? null,
                        'captured_longitude' => $item['captured_longitude'] ?? null,
                        'captured_at' => $item['captured_at'] ?? now(),
                        'metadata' => $item['metadata'] ?? null,
                    ]);
                }
            }

            $targetStatus = ($activity->approval_policy === 'auto') ? 'completed' : 'submitted';

            $activity->update([
                'status' => $targetStatus,
                'actual_end_at' => now(),
                'completion_notes' => $notes,
                'payload' => $payload ?? $activity->payload,
            ]);

            $this->recordEvent($activity, $user, $fromStatus, $targetStatus, 'Activity completed by user');

            return $activity;
        });
    }

    /**
     * Record an immutable state transition event.
     */
    public function recordEvent(Activity $activity, ?User $user, string $fromStatus, string $toStatus, ?string $reason = null): ActivityEvent
    {
        return ActivityEvent::create([
            'id' => (string) Str::uuid(),
            'activity_id' => $activity->id,
            'user_id' => $user?->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
