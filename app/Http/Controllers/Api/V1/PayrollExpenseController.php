<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\ExpenseClaim;
use App\Models\PayRun;

use App\Services\AssetLifecycleService;
use App\Services\ExpenseManagementService;
use App\Services\PayrollEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollExpenseController extends Controller
{
    public function __construct(
        protected PayrollEngineService $payrollService,
        protected ExpenseManagementService $expenseService,
        protected AssetLifecycleService $assetService
    ) {}

    public function createPayRun(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $payRun = $this->payrollService->calculatePayRun(
            initiator: $request->user(),
            periodStart: Carbon::parse($validated['period_start']),
            periodEnd: Carbon::parse($validated['period_end'])
        );

        return response()->json([
            'success' => true,
            'data' => $payRun,
        ], 201);
    }

    public function reviewPayRun(Request $request, PayRun $payRun): JsonResponse
    {
        $reviewed = $this->payrollService->reviewPayRun($payRun);

        return response()->json([
            'success' => true,
            'data' => $reviewed,
        ]);
    }

    public function approvePayRun(Request $request, PayRun $payRun): JsonResponse
    {
        $approved = $this->payrollService->approvePayRun($payRun, $request->user());

        return response()->json([
            'success' => true,
            'data' => $approved,
        ]);
    }

    public function submitExpense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'merchant' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'receipt_url' => 'nullable|string',
            'activity_id' => 'nullable|exists:activities,id',
            'is_offline_captured' => 'nullable|boolean',
        ]);

        $claim = $this->expenseService->submitClaim(
            user: $request->user(),
            category: $validated['category'],
            merchant: $validated['merchant'],
            amount: (float) $validated['amount'],
            receiptUrl: $validated['receipt_url'] ?? null,
            activityId: $validated['activity_id'] ?? null,
            isOfflineCaptured: $validated['is_offline_captured'] ?? false
        );

        return response()->json([
            'success' => true,
            'data' => $claim,
        ], 201);
    }

    public function assignAsset(Request $request, Asset $asset): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to_user_id' => 'required|exists:users,id',
            'signature' => 'nullable|string',
        ]);

        $user = \App\Models\User::findOrFail($validated['assigned_to_user_id']);

        $assignment = $this->assetService->assignAsset(
            asset: $asset,
            user: $user,
            signature: $validated['signature'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ]);
    }
}
