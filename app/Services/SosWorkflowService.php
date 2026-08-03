<?php

namespace App\Services;

use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Support\Str;

class SosWorkflowService
{
    /**
     * Trigger high-priority emergency SOS request.
     */
    public function triggerSos(User $user, float $latitude, float $longitude): SosRequest
    {
        return SosRequest::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'user_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 'active',
            'triggered_at' => now(),
        ]);
    }

    /**
     * Assign responder and transition status to responding.
     */
    public function assignResponder(SosRequest $sos, User $responder): SosRequest
    {
        $sos->update([
            'responder_id' => $responder->id,
            'status' => 'responding',
        ]);

        return $sos;
    }

    /**
     * Resolve SOS request.
     */
    public function resolveSos(SosRequest $sos, string $notes, bool $isFalseAlarm = false): SosRequest
    {
        $sos->update([
            'status' => $isFalseAlarm ? 'false_alarm' : 'resolved',
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        return $sos;
    }
}
