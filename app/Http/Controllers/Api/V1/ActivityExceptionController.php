<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityException;
use App\Models\User;
use App\Services\ActivityExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ActivityExceptionController extends Controller
{
    public function __construct(
        protected ActivityExceptionService $exceptionService
    ) {}

    public function index(): JsonResponse
    {
        if (!Schema::hasTable('activity_exceptions')) {
            return response()->json([
                'success' => false,
                'message' => 'Database schema incomplete: activity_exceptions table missing. Run php artisan migrate.',
            ], 500);
        }

        try {
            $exceptions = ActivityException::with(['activity', 'user', 'reviewer'])
                ->where('status', 'pending')
                ->latest()
                ->paginate(25);

            return response()->json([
                'success' => true,
                'data' => $exceptions,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exception queue: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (!Schema::hasTable('activity_exceptions')) {
            return response()->json([
                'success' => false,
                'message' => 'Database schema incomplete: activity_exceptions table missing. Run php artisan migrate.',
            ], 500);
        }

        try {
            $validated = $request->validate([
                'activity_id' => 'nullable|integer',
                'exception_type' => 'required|string',
                'reason' => 'required|string',
                'outlet_name' => 'nullable|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
            }
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store exception request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, string $id): mixed
    {
        if (!Schema::hasTable('activity_exceptions')) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['message' => 'Database schema incomplete: activity_exceptions table missing.']);
            }
            return response()->json([
                'success' => false,
                'message' => 'Database schema incomplete: activity_exceptions table missing. Run php artisan migrate.',
            ], 500);
        }

        try {
            $notes = $request->input('notes', 'Approved via Web Admin Supervisory Queue');

            $exception = ActivityException::where('id', $id)
                ->orWhere('client_uuid', $id)
                ->first();

            if (!$exception) {
                if ($request->header('X-Inertia')) {
                    return redirect()->back()->withErrors(['message' => 'Exception not found.']);
                }
                return response()->json(['success' => false, 'message' => 'Exception not found.'], 404);
            }

            $reviewer = $request->user();
            if (!$reviewer) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
            }

            $approved = $this->exceptionService->approveException($exception, $reviewer, $notes);

            if ($request->header('X-Inertia')) {
                return redirect()->back()->with('success', 'Exception approved successfully.');
            }

            return response()->json([
                'success' => true,
                'data' => $approved,
            ]);
        } catch (\Throwable $e) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['message' => 'Failed to approve: ' . $e->getMessage()]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to process exception approval: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, string $id): mixed
    {
        if (!Schema::hasTable('activity_exceptions')) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['message' => 'Database schema incomplete: activity_exceptions table missing.']);
            }
            return response()->json([
                'success' => false,
                'message' => 'Database schema incomplete: activity_exceptions table missing. Run php artisan migrate.',
            ], 500);
        }

        try {
            $notes = $request->input('notes', 'Rejected via Web Admin Supervisory Queue');

            $exception = ActivityException::where('id', $id)
                ->orWhere('client_uuid', $id)
                ->first();

            if (!$exception) {
                if ($request->header('X-Inertia')) {
                    return redirect()->back()->withErrors(['message' => 'Exception not found.']);
                }
                return response()->json(['success' => false, 'message' => 'Exception not found.'], 404);
            }

            $reviewer = $request->user();
            if (!$reviewer) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
            }

            $rejected = $this->exceptionService->rejectException($exception, $reviewer, $notes);

            if ($request->header('X-Inertia')) {
                return redirect()->back()->with('success', 'Exception rejected successfully.');
            }

            return response()->json([
                'success' => true,
                'data' => $rejected,
            ]);
        } catch (\Throwable $e) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['message' => 'Failed to reject: ' . $e->getMessage()]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to process exception rejection: ' . $e->getMessage(),
            ], 500);
        }
    }
}
