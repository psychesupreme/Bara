<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionClaim;
use App\Models\SalesOrder;
use Illuminate\Support\Str;

class TradePromotionService
{
    /**
     * Auto-apply active promotions to order lines (TP-002).
     */
    public function evaluateAndApplyPromotions(SalesOrder $order): SalesOrder
    {
        $activePromos = Promotion::where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString());
            })->get();

        $totalPromoDiscount = 0.0;

        foreach ($activePromos as $promo) {
            if ($promo->budget_cap > 0 && $promo->spent_amount >= $promo->budget_cap) {
                continue; // Budget cap reached
            }

            if ($promo->promo_type === 'percentage_discount') {
                $discount = round(($order->subtotal_amount * $promo->discount_percentage) / 100.0, 2);
                $totalPromoDiscount += $discount;
            } elseif ($promo->promo_type === 'buy_x_get_y' && $promo->buy_product_id) {
                foreach ($order->lines as $line) {
                    if ($line->product_id === $promo->buy_product_id && $line->quantity >= $promo->buy_quantity) {
                        $unitsEligible = (int) floor($line->quantity / $promo->buy_quantity);
                        $freeQty = $unitsEligible * $promo->get_quantity;

                        // Create promotion claim record
                        PromotionClaim::create([
                            'promotion_id' => $promo->id,
                            'sales_order_id' => $order->id,
                            'customer_id' => $order->customer_id,
                            'claimed_amount' => $freeQty * $line->unit_price,
                            'status' => 'approved',
                        ]);

                        $promo->increment('spent_amount', $freeQty * $line->unit_price);
                    }
                }
            }
        }

        if ($totalPromoDiscount > 0) {
            $newDiscount = $order->discount_amount + $totalPromoDiscount;
            $newTotal = max(0.0, $order->subtotal_amount - $newDiscount);
            $order->update([
                'discount_amount' => $newDiscount,
                'total_amount' => $newTotal,
            ]);
        }

        return $order->fresh();
    }
}
