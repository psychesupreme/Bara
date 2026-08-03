<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityException;
use App\Services\ActivityExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityExceptionController extends Controller
{
    public function __construct(
        protected ActivityExceptionService $exceptionService
    ) {}

    public function index(): JsonResponse
    {
        $exceptions = ActivityException::with(['activity', 'user', 'reviewer'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $exceptions,
        ]);
    }

    public function approve(Request $request, ActivityException $exception): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $approved = $this->exceptionService->approveException($exception, $request->user(), $validated['notes']);

        return response()->json([
            'success' => true,
            'data' => $approved,
        ]);
    }

    public function reject(Request $request, ActivityException $exception): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $rejected = $this->exceptionService->rejectException($exception, $request->user(), $validated['notes']);

        return response()->json([
            'success' => true,
            'data' => $rejected,
        ]);
    }
}
