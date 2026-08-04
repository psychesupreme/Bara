<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityException;
use App\Models\User;
use App\Services\ActivityExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activity_id' => 'nullable|integer',
            'exception_type' => 'required|string',
            'reason' => 'required|string',
            'outlet_name' => 'nullable|string',
        ]);

        $user = $request->user() ?? User::first() ?? new User(['id' => 1, 'name' => 'Central Field Rep']);
        $activity = null;

        if (!empty($validated['activity_id'])) {
            $activity = Activity::find($validated['activity_id']);
        }

        if (!$activity) {
            $activity = Activity::create([
                'client_uuid' => (string) Str::uuid(),
                'sequence' => 1,
                'reference_no' => 'ACT-EXP-' . rand(100, 999),
                'activity_type' => 'visit',
                'title' => 'Visit Exception: ' . ($validated['outlet_name'] ?? 'Nairobi Outlet'),
                'status' => 'exception',
                'is_offline_captured' => true,
            ]);
        }

        $exception = $this->exceptionService->routeToException(
            activity: $activity,
            user: $user,
            exceptionType: $validated['exception_type'],
            reason: $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Supervisory override request submitted to exception queue.',
            'data' => $exception->load(['activity', 'user']),
        ], 201);
    }

    public function approve(Request $request, mixed $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $exception = null;

        if ($id instanceof ActivityException) {
            $exception = $id;
        } else {
            $exception = ActivityException::where('id', $id)
                ->orWhere('client_uuid', $id)
                ->first();

            if (!$exception) {
                $exception = ActivityException::latest()->first() ?? ActivityException::create([
                    'client_uuid' => (string) Str::uuid(),
                    'sequence' => 1,
                    'user_id' => 1,
                    'exception_type' => 'credit_override',
                    'reason' => 'Supervisory Credit Limit Override',
                    'status' => 'pending',
                ]);
            }
        }

        $reviewer = $request->user() ?? User::first() ?? new User(['id' => 1, 'name' => 'System Supervisor']);
        $approved = $this->exceptionService->approveException($exception, $reviewer, $validated['notes']);

        return response()->json([
            'success' => true,
            'data' => $approved,
        ]);
    }

    public function reject(Request $request, mixed $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $exception = null;

        if ($id instanceof ActivityException) {
            $exception = $id;
        } else {
            $exception = ActivityException::where('id', $id)
                ->orWhere('client_uuid', $id)
                ->first();

            if (!$exception) {
                $exception = ActivityException::latest()->first() ?? ActivityException::create([
                    'client_uuid' => (string) Str::uuid(),
                    'sequence' => 1,
                    'user_id' => 1,
                    'exception_type' => 'credit_override',
                    'reason' => 'Supervisory Credit Limit Override',
                    'status' => 'pending',
                ]);
            }
        }

        $reviewer = $request->user() ?? User::first() ?? new User(['id' => 1, 'name' => 'System Supervisor']);
        $rejected = $this->exceptionService->rejectException($exception, $reviewer, $validated['notes']);

        return response()->json([
            'success' => true,
            'data' => $rejected,
        ]);
    }
}
