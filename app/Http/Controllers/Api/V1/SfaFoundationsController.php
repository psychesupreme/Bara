<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CommercialNode;
use App\Models\CommercialProduct;
use App\Models\Customer;
use App\Models\User;
use App\Services\CommercialScopeResolver;
use App\Services\PriceWaterfallEngine;
use App\Services\RoutePlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SfaFoundationsController extends Controller
{
    public function __construct(
        protected CommercialScopeResolver $scopeResolver,
        protected RoutePlanningService $routePlanningService,
        protected PriceWaterfallEngine $priceEngine
    ) {}

    public function previewAccess(Request $request, User $user): JsonResponse
    {
        $preview = $this->scopeResolver->previewUserAccess($user);

        return response()->json([
            'success' => true,
            'data' => $preview,
        ]);
    }

    public function checkProspectDuplicates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'phone' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $duplicates = $this->routePlanningService->checkForDuplicates($validated);

        return response()->json([
            'success' => true,
            'has_duplicates' => !empty($duplicates),
            'duplicates' => $duplicates,
        ]);
    }

    public function resolvePrice(Request $request, CommercialProduct $product): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'deal_id' => 'nullable|string',
            'currency' => 'nullable|string',
        ]);

        $customer = isset($validated['customer_id']) ? Customer::find($validated['customer_id']) : null;

        $resolved = $this->priceEngine->resolvePrice(
            product: $product,
            customer: $customer,
            dealId: $validated['deal_id'] ?? null,
            currency: $validated['currency'] ?? 'KES'
        );

        return response()->json([
            'success' => true,
            'data' => $resolved,
        ]);
    }
}
