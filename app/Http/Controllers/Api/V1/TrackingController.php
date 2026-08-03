<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TrackingSession;
use App\Services\TrackingSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct(
        protected TrackingSessionManager $trackingSessionManager
    ) {}

    public function startSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purpose' => 'nullable|string',
        ]);

        $session = $this->trackingSessionManager->startSession(
            user: $request->user(),
            purpose: $validated['purpose'] ?? 'shift'
        );

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    public function stopSession(Request $request, TrackingSession $session): JsonResponse
    {
        $stopped = $this->trackingSessionManager->stopSession($session);

        return response()->json([
            'success' => true,
            'data' => $stopped,
        ]);
    }

    public function ingestStream(Request $request, TrackingSession $session): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|array|min:1|max:100',
            'points.*.latitude' => 'required|numeric',
            'points.*.longitude' => 'required|numeric',
            'points.*.recorded_at' => 'required|date',
            'points.*.accuracy_meters' => 'nullable|numeric',
        ]);

        $result = $this->trackingSessionManager->ingestPoints($session, $validated['points']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
