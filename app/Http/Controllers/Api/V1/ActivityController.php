<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivityLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(
        protected ActivityLifecycleService $activityLifecycleService
    ) {}

    /**
     * List assigned activities for current user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $activities = Activity::with(['fieldLocation', 'assignments'])
            ->whereHas('assignments', function ($query) use ($user) {
                $query->where('assignee_id', $user->id);
            })
            ->latest()
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Presence-gated activity start.
     */
    public function start(Request $request, Activity $activity): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'gps_accuracy_meters' => 'required|numeric',
            'device_id' => 'nullable|string',
        ]);

        $verification = $this->activityLifecycleService->startActivity(
            activity: $activity,
            user: $request->user(),
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            gpsAccuracyMeters: (float) $validated['gps_accuracy_meters'],
            deviceId: $validated['device_id'] ?? null
        );

        $passed = $verification->verification_status === 'passed';

        return response()->json([
            'success' => $passed,
            'verification' => $verification,
            'activity' => $activity->fresh(),
        ], $passed ? 200 : 422);
    }

    /**
     * Complete activity with evidence payload.
     */
    public function complete(Request $request, Activity $activity): JsonResponse
    {
        $validated = $request->validate([
            'completion_notes' => 'nullable|string',
            'evidence' => 'nullable|array',
            'payload' => 'nullable|array',
        ]);

        $completedActivity = $this->activityLifecycleService->completeActivity(
            activity: $activity,
            user: $request->user(),
            notes: $validated['completion_notes'] ?? null,
            evidenceData: $validated['evidence'] ?? [],
            payload: $validated['payload'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $completedActivity->load(['evidence', 'events']),
        ]);
    }
}
