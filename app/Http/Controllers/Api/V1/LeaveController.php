<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\LeaveManagementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveManagementService $leaveManagementService
    ) {}

    public function balances(Request $request): JsonResponse
    {
        $balances = LeaveBalance::where('user_id', $request->user()->id)->get();

        return response()->json([
            'success' => true,
            'data' => $balances,
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type' => 'required|string|in:annual,sick,compassionate,unpaid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $leaveRequest = $this->leaveManagementService->submitLeaveRequest(
            user: $request->user(),
            leaveType: $validated['leave_type'],
            startDate: Carbon::parse($validated['start_date']),
            endDate: Carbon::parse($validated['end_date']),
            reason: $validated['reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $leaveRequest,
        ], 201);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $approved = $this->leaveManagementService->approveLeave(
            request: $leaveRequest,
            reviewer: $request->user(),
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $approved,
        ]);
    }
}
