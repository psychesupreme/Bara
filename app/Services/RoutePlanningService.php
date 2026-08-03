<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RoutePlanningService
{
    public function __construct(
        protected VerificationResultEngine $verificationEngine
    ) {}

    /**
     * Check for duplicate outlet prospects against name, tax number, phone, or PostGIS coordinates.
     */
    public function checkForDuplicates(array $prospectData): array
    {
        $duplicates = [];

        // Check tax number
        if (!empty($prospectData['tax_number'])) {
            $existingTax = Customer::where('tax_number', $prospectData['tax_number'])->first();
            if ($existingTax) {
                $duplicates[] = [
                    'field' => 'tax_number',
                    'matched_customer_id' => $existingTax->id,
                    'matched_customer_name' => $existingTax->name,
                ];
            }
        }

        // Check phone
        if (!empty($prospectData['phone'])) {
            $existingPhone = Customer::where('phone', $prospectData['phone'])->first();
            if ($existingPhone) {
                $duplicates[] = [
                    'field' => 'phone',
                    'matched_customer_id' => $existingPhone->id,
                    'matched_customer_name' => $existingPhone->name,
                ];
            }
        }

        // Check spatial proximity (< 50 meters)
        if (isset($prospectData['latitude']) && isset($prospectData['longitude'])) {
            $nearbyCustomers = Customer::whereNotNull('latitude')->whereNotNull('longitude')->get();

            foreach ($nearbyCustomers as $customer) {
                $distance = $this->verificationEngine->calculateDistanceMeters(
                    (float) $prospectData['latitude'],
                    (float) $prospectData['longitude'],
                    (float) $customer->latitude,
                    (float) $customer->longitude
                );

                if ($distance <= 50.0) {
                    $duplicates[] = [
                        'field' => 'coordinates',
                        'distance_meters' => round($distance, 2),
                        'matched_customer_id' => $customer->id,
                        'matched_customer_name' => $customer->name,
                    ];
                    break;
                }
            }
        }

        return $duplicates;
    }

    /**
     * Create route plan template with guided call steps.
     */
    public function createRoutePlan(
        User $salesRep,
        string $name,
        string $code,
        array $visitDays,
        array $stops
    ): RoutePlan {
        $plan = RoutePlan::create([
            'name' => $name,
            'code' => $code,
            'sales_rep_id' => $salesRep->id,
            'visit_days' => $visitDays,
            'is_active' => true,
        ]);

        $order = 1;
        foreach ($stops as $stopData) {
            RouteStop::create([
                'route_plan_id' => $plan->id,
                'customer_id' => $stopData['customer_id'],
                'stop_order' => $order++,
                'guided_call_steps' => $stopData['guided_call_steps'] ?? [
                    'check_in', 'stock_check', 'order_entry', 'promotions', 'merchandising', 'collection', 'check_out'
                ],
            ]);
        }

        return $plan;
    }
}
