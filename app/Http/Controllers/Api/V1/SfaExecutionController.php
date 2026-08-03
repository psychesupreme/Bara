<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Services\GuidedSellingService;
use App\Services\MerchandisingExecutionService;
use App\Services\OrderOrchestrationService;
use App\Services\TradePromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SfaExecutionController extends Controller
{
    public function __construct(
        protected GuidedSellingService $guidedSellingService,
        protected OrderOrchestrationService $orderService,
        protected TradePromotionService $promotionService,
        protected MerchandisingExecutionService $merchandisingService
    ) {}

    public function customer360(Request $request, Customer $customer): JsonResponse
    {
        $profile = $this->guidedSellingService->getCustomer360Profile($customer);

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:commercial_products,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.price_rule_code' => 'nullable|string',
            'lines.*.discount_amount' => 'nullable|numeric',
            'activity_id' => 'nullable|exists:activities,id',
            'is_offline_captured' => 'nullable|boolean',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        $order = $this->orderService->createOrder(
            salesRep: $request->user(),
            customer: $customer,
            lines: $validated['lines'],
            activityId: $validated['activity_id'] ?? null,
            isOfflineCaptured: $validated['is_offline_captured'] ?? false
        );

        // Auto-apply eligible trade promotions
        $order = $this->promotionService->evaluateAndApplyPromotions($order);

        return response()->json([
            'success' => true,
            'data' => $order,
        ], 201);
    }

    public function transitionOrder(Request $request, SalesOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $transitioned = $this->orderService->transitionStatus(
            order: $order,
            newStatus: $validated['status'],
            user: $request->user(),
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $transitioned,
        ]);
    }

    public function recordMerchandising(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'observations' => 'required|array|min:1',
            'observations.*.product_id' => 'required|exists:commercial_products,id',
            'observations.*.is_in_stock' => 'required|boolean',
            'observations.*.facing_count' => 'nullable|integer',
            'observations.*.shelf_price' => 'nullable|numeric',
            'evidence_photo_url' => 'nullable|string',
            'posm_condition' => 'nullable|string',
            'activity_id' => 'nullable|exists:activities,id',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        $observation = $this->merchandisingService->recordObservation(
            user: $request->user(),
            customer: $customer,
            productObservations: $validated['observations'],
            photoUrl: $validated['evidence_photo_url'] ?? null,
            posmCondition: $validated['posm_condition'] ?? 'good',
            activity: isset($validated['activity_id']) ? \App\Models\Activity::find($validated['activity_id']) : null
        );

        return response()->json([
            'success' => true,
            'data' => $observation,
        ], 201);
    }
}
