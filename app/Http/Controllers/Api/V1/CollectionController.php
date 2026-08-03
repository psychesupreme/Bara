<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Collection;
use App\Services\CollectionService;
use App\Services\MpesaStkAdapter;
use App\Services\PromiseToPayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function __construct(
        protected CollectionService $collectionService,
        protected MpesaStkAdapter $mpesaStkAdapter,
        protected PromiseToPayService $promiseToPayService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $collections = Collection::with(['collector', 'activity', 'allocations', 'reversal'])
            ->latest()
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $collections,
        ]);
    }

    public function capture(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|string|in:mpesa_stk,cash,cheque,bank_transfer',
            'currency' => 'nullable|string',
            'activity_id' => 'nullable|exists:activities,id',
            'gateway_reference' => 'nullable|string',
            'is_offline_captured' => 'nullable|boolean',
            'allocations' => 'nullable|array',
        ]);

        $activity = isset($validated['activity_id']) ? Activity::find($validated['activity_id']) : null;

        $collection = $this->collectionService->captureCollection(
            collector: $request->user(),
            customerId: $validated['customer_id'],
            amount: (float) $validated['amount'],
            paymentMode: $validated['payment_mode'],
            currency: $validated['currency'] ?? 'KES',
            activity: $activity,
            gatewayReference: $validated['gateway_reference'] ?? null,
            isOfflineCaptured: $validated['is_offline_captured'] ?? false,
            invoiceAllocations: $validated['allocations'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $collection->load('allocations'),
        ], 201);
    }

    public function initiateStk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'account_reference' => 'required|string',
        ]);

        $stkResponse = $this->mpesaStkAdapter->initiateStkPush(
            phoneNumber: $validated['phone_number'],
            amount: (float) $validated['amount'],
            accountReference: $validated['account_reference']
        );

        return response()->json([
            'success' => true,
            'data' => $stkResponse,
        ]);
    }

    public function reconcile(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $reconciliation = $this->collectionService->reconcileCollection(
            collection: $collection,
            reconciler: $request->user(),
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $reconciliation,
        ]);
    }

    public function reverse(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $reversal = $this->collectionService->reverseCollection(
            collection: $collection,
            reversedBy: $request->user(),
            reason: $validated['reason']
        );

        return response()->json([
            'success' => true,
            'data' => $reversal,
        ]);
    }

    public function recordPromise(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|string',
            'promised_amount' => 'required|numeric|min:0.01',
            'promised_date' => 'required|date',
            'activity_id' => 'nullable|exists:activities,id',
            'notes' => 'nullable|string',
        ]);

        $activity = isset($validated['activity_id']) ? Activity::find($validated['activity_id']) : null;

        $promise = $this->promiseToPayService->recordPromise(
            customerId: $validated['customer_id'],
            promisedAmount: (float) $validated['promised_amount'],
            promisedDate: Carbon::parse($validated['promised_date']),
            activity: $activity,
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $promise,
        ], 201);
    }
}
