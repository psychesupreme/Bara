<?php

namespace App\Services;

use App\Models\CommercialProduct;
use App\Models\Customer;
use App\Models\PriceRule;

class PriceWaterfallEngine
{
    /**
     * Resolve product unit price in strict precedence:
     * Deal (7) -> Outlet (6) -> Customer Group (5) -> Channel (4) -> Structure (3) -> Country (2) -> Base (1)
     */
    public function resolvePrice(
        CommercialProduct $product,
        ?Customer $customer = null,
        ?string $dealId = null,
        string $currency = 'KES'
    ): array {
        $now = now();
        $query = PriceRule::where('product_id', $product->id)
            ->where('currency', $currency)
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            });

        $rules = $query->get();

        // Level Priority Mapping
        $precedence = [
            'deal' => 7,
            'outlet' => 6,
            'customer_group' => 5,
            'channel' => 4,
            'structure' => 3,
            'country' => 2,
            'base' => 1,
        ];

        $matchingRule = null;
        $highestPriority = -1;

        foreach ($rules as $rule) {
            $matched = false;
            $level = strtolower($rule->level_type);

            switch ($level) {
                case 'deal':
                    if ($dealId && $rule->level_id === $dealId) {
                        $matched = true;
                    }
                    break;

                case 'outlet':
                    if ($customer && $rule->level_id === $customer->id) {
                        $matched = true;
                    }
                    break;

                case 'customer_group':
                    if ($customer && $customer->extension && $rule->level_id === $customer->extension->segment) {
                        $matched = true;
                    }
                    break;

                case 'channel':
                    if ($customer && $customer->extension && $rule->level_id === $customer->extension->channel) {
                        $matched = true;
                    }
                    break;

                case 'structure':
                    if ($customer && $rule->level_id === $customer->commercial_node_id) {
                        $matched = true;
                    }
                    break;

                case 'country':
                    if ($customer && $rule->level_id === $customer->county) {
                        $matched = true;
                    }
                    break;

                case 'base':
                    $matched = true;
                    break;
            }

            if ($matched) {
                $priorityWeight = $precedence[$level] ?? 0;
                if ($priorityWeight > $highestPriority) {
                    $highestPriority = $priorityWeight;
                    $matchingRule = $rule;
                }
            }
        }

        if (!$matchingRule) {
            return [
                'unit_price' => 0.0,
                'currency' => $currency,
                'price_rule_code' => 'DEFAULT_ZERO',
                'level_type' => 'unresolved',
                'level_id' => null,
            ];
        }

        return [
            'unit_price' => (float) $matchingRule->unit_price,
            'currency' => $matchingRule->currency,
            'price_rule_code' => $matchingRule->code,
            'price_rule_id' => $matchingRule->id,
            'level_type' => $matchingRule->level_type,
            'level_id' => $matchingRule->level_id,
        ];
    }
}
