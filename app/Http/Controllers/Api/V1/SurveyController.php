<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\FormVersion;
use App\Services\FollowUpAutomationService;
use App\Services\SurveyEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function __construct(
        protected SurveyEngineService $surveyEngineService,
        protected FollowUpAutomationService $followUpAutomationService
    ) {}

    public function submit(Request $request, FormVersion $formVersion): JsonResponse
    {
        $validated = $request->validate([
            'activity_id' => 'nullable|exists:activities,id',
            'answers' => 'required|array',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $activity = isset($validated['activity_id']) ? Activity::find($validated['activity_id']) : null;

        $response = $this->surveyEngineService->submitResponse(
            formVersion: $formVersion,
            respondent: $request->user(),
            answers: $validated['answers'],
            activity: $activity,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null
        );

        if ($activity) {
            $this->followUpAutomationService->evaluateAndScheduleFollowUp($activity, $response->score);
        }

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 201);
    }
}
