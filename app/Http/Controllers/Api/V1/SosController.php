<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SosRequest;
use App\Services\SosWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SosController extends Controller
{
    public function __construct(
        protected SosWorkflowService $sosWorkflowService
    ) {}

    public function trigger(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $sos = $this->sosWorkflowService->triggerSos(
            user: $request->user(),
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude']
        );

        return response()->json([
            'success' => true,
            'data' => $sos,
        ], 201);
    }

    public function respond(Request $request, SosRequest $sos): JsonResponse
    {
        $updated = $this->sosWorkflowService->assignResponder($sos, $request->user());

        return response()->json([
            'success' => true,
            'data' => $updated,
        ]);
    }

    public function resolve(Request $request, SosRequest $sos): JsonResponse
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string',
            'is_false_alarm' => 'nullable|boolean',
        ]);

        $resolved = $this->sosWorkflowService->resolveSos(
            sos: $sos,
            notes: $validated['resolution_notes'],
            isFalseAlarm: $validated['is_false_alarm'] ?? false
        );

        return response()->json([
            'success' => true,
            'data' => $resolved,
        ]);
    }
}
