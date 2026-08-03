<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\MerchObservation;
use App\Models\MerchObservationLine;
use App\Models\User;
use Illuminate\Support\Str;

class MerchandisingExecutionService
{
    public function __construct(
        protected FollowUpAutomationService $followUpAutomationService
    ) {}

    /**
     * Submit merchandising observation and calculate MSL compliance percentage (ME-001).
     * Automatically triggers corrective activity if MSL compliance score < 70% (ME-005).
     */
    public function recordObservation(
        User $user,
        Customer $customer,
        array $productObservations,
        ?string $photoUrl = null,
        ?string $posmCondition = 'good',
        ?Activity $activity = null
    ): MerchObservation {
        $totalItems = count($productObservations);
        $inStockCount = 0;
        $totalFacings = 0;

        foreach ($productObservations as $item) {
            if (!empty($item['is_in_stock'])) {
                $inStockCount++;
            }
            $totalFacings += (int) ($item['facing_count'] ?? 0);
        }

        $mslScore = ($totalItems > 0) ? round(($inStockCount / $totalItems) * 100.0, 2) : 100.0;
        $shareOfShelf = min(100.0, round(($totalFacings / max(1, $totalItems * 5)) * 100.0, 2));

        $observation = MerchObservation::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'customer_id' => $customer->id,
            'activity_id' => $activity?->id,
            'user_id' => $user->id,
            'msl_compliance_score' => $mslScore,
            'share_of_shelf_percentage' => $shareOfShelf,
            'evidence_photo_url' => $photoUrl,
            'posm_condition' => $posmCondition ?? 'good',
        ]);

        foreach ($productObservations as $item) {
            MerchObservationLine::create([
                'merch_observation_id' => $observation->id,
                'product_id' => $item['product_id'],
                'is_in_stock' => $item['is_in_stock'] ?? true,
                'facing_count' => $item['facing_count'] ?? 0,
                'shelf_price' => $item['shelf_price'] ?? 0.00,
            ]);
        }

        // Trigger automated corrective activity if MSL score is below 70% (ME-005)
        if ($mslScore < 70.0 && $activity) {
            $activity->update(['outcome_code' => 'MSL_NON_COMPLIANT']);
            $this->followUpAutomationService->evaluateAndScheduleFollowUp($activity);
        }

        return $observation->load('lines');
    }
}
