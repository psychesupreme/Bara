<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOutletExtension;
use App\Models\OrderEvent;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderOrchestrationService
{
    public function __construct(
        protected KraEtimsAdapter $kraEtimsAdapter
    ) {}

    /**
     * Create a new sales order in draft status.
     * PRD Section 7.6: Ensures immutable price rule snapshotting (price_rule_id & raw applied discount rates).
     */
    public function createOrder(
        User $salesRep,
        Customer $customer,
        array $lines,
        ?string $activityId = null,
        bool $isOfflineCaptured = false
    ): SalesOrder {
        return DB::transaction(function () use ($salesRep, $customer, $lines, $activityId, $isOfflineCaptured) {
            $orderNumber = 'SO-' . date('Y') . '-' . Str::upper(Str::random(6));

            $order = SalesOrder::create([
                'client_uuid' => (string) Str::uuid(),
                'sequence' => 1,
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'sales_rep_id' => $salesRep->id,
                'activity_id' => $activityId,
                'currency' => 'KES',
                'status' => 'draft',
                'is_offline_captured' => $isOfflineCaptured,
            ]);

            $subtotal = 0.0;
            $totalDiscount = 0.0;

            foreach ($lines as $lineData) {
                $lineSubtotal = round($lineData['quantity'] * $lineData['unit_price'], 2);
                $lineDiscount = $lineData['discount_amount'] ?? 0.0;
                $discountRate = $lineSubtotal > 0 ? round(($lineDiscount / $lineSubtotal) * 100.0, 2) : 0.0;
                $lineTotal = max(0.0, $lineSubtotal - $lineDiscount);

                SalesOrderLine::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $lineData['product_id'],
                    'price_rule_id' => $lineData['price_rule_id'] ?? null,
                    'quantity' => $lineData['quantity'],
                    'unit_price' => $lineData['unit_price'],
                    'price_rule_code' => $lineData['price_rule_code'] ?? 'PR-CUST-007',
                    'discount_amount' => $lineDiscount,
                    'discount_rate' => $discountRate,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $totalDiscount += $lineDiscount;
            }

            $totalAmount = max(0.0, $subtotal - $totalDiscount);
            $taxAmount = round($totalAmount - ($totalAmount / 1.16), 2); // 16% VAT

            $order->update([
                'subtotal_amount' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            // Append immutable order event (OM-008)
            OrderEvent::create([
                'id' => (string) Str::uuid(),
                'sales_order_id' => $order->id,
                'user_id' => $salesRep->id,
                'from_status' => null,
                'to_status' => 'draft',
                'event_type' => 'ORDER_CREATED',
                'notes' => 'Order created in draft status with immutable price rule snapshotting.',
                'created_at' => now(),
            ]);

            return $order->load('lines');
        });
    }

    /**
     * Execute Order State Machine Transitions
     */
    public function transitionStatus(
        SalesOrder $order,
        string $newStatus,
        ?User $user = null,
        ?string $notes = null
    ): SalesOrder {
        $allowedStatuses = [
            'draft',
            'submitted',
            'pending_approval',
            'approved',
            'rejected',
            'credit_hold',
            'allocated',
            'stock_allocated',
            'processing',
            'dispatched',
            'delivered',
            'invoiced',
            'closed',
        ];

        if (!in_array($newStatus, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid order status: {$newStatus}");
        }

        // Compliance Gatekeeper Check (OM-002): Block approval if total amount exceeds credit limit
        if ($newStatus === 'approved') {
            $extension = CustomerOutletExtension::where('customer_id', $order->customer_id)->first();
            if ($extension && $extension->credit_limit > 0) {
                $currentBalance = $extension->outstanding_balance ?? 0.0;
                if (($currentBalance + $order->total_amount) > $extension->credit_limit) {
                    throw new InvalidArgumentException("Order total KES {$order->total_amount} exceeds customer credit limit KES {$extension->credit_limit}.");
                }
            }
        }

        $fromStatus = $order->status;

        DB::transaction(function () use ($order, $fromStatus, $newStatus, $user, $notes) {
            $orderData = ['status' => $newStatus];

            if ($newStatus === 'dispatched') {
                $etimsPayload = $this->kraEtimsAdapter->generateElectronicTaxReceipt($order);
                $orderData['etims_receipt_number'] = $etimsPayload['etims_receipt_number'];
                $orderData['etims_signature'] = $etimsPayload['etims_signature'];
                $orderData['etims_qr_code'] = $etimsPayload['etims_qr_code'];
            }

            $order->update($orderData);

            OrderEvent::create([
                'id' => (string) Str::uuid(),
                'sales_order_id' => $order->id,
                'user_id' => $user ? $user->id : null,
                'from_status' => $fromStatus,
                'to_status' => $newStatus,
                'event_type' => 'STATUS_TRANSITION',
                'notes' => $notes ?? "Order status changed from {$fromStatus} to {$newStatus}.",
                'created_at' => now(),
            ]);
        });

        return $order->fresh(['lines']);
    }
}
