<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\MerchObservation;
use App\Models\SalesOrder;

class GuidedSellingService
{
    /**
     * Build unified Customer 360 profile context (SE-001).
     */
    public function getCustomer360Profile(Customer $customer): array
    {
        $customer->load('extension', 'commercialNode');

        $openOrders = SalesOrder::where('customer_id', $customer->id)
            ->whereIn('status', ['draft', 'pending_approval', 'approved', 'allocated', 'dispatched'])
            ->get();

        $collections = Collection::where('customer_id', $customer->id)
            ->latest('captured_at')
            ->take(5)
            ->get();

        $recentActivities = Activity::where('title', 'like', "%{$customer->name}%")
            ->latest()
            ->take(5)
            ->get();

        $latestMerch = MerchObservation::where('customer_id', $customer->id)
            ->latest()
            ->first();

        return [
            'customer' => $customer,
            'commercial_details' => $customer->extension,
            'open_orders_count' => $openOrders->count(),
            'open_orders_total' => $openOrders->sum('total_amount'),
            'recent_collections' => $collections,
            'recent_activities' => $recentActivities,
            'latest_msl_score' => $latestMerch?->msl_compliance_score ?? 100.0,
            'latest_share_of_shelf' => $latestMerch?->share_of_shelf_percentage ?? 0.0,
        ];
    }
}
