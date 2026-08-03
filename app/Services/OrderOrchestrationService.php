<?php

namespace App\Services;

use App\Models\Customer;
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
                $lineTotal = max(0.0, $lineSubtotal - $lineDiscount);

                SalesOrderLine::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $lineData['product_id'],
                    'quantity' => $lineData['quantity'],
                    'unit_price' => $lineData['unit_price'],
                    'price_rule_code' => $lineData['price_rule_code'] ?? 'BASE',
                    'discount_amount' => $lineDiscount,
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
                'notes' => 'Order created in draft status.',
                'created_at' => now(),
            ]);

            return $order->load('lines');
        });
    }

    /**
     * Transition order status through state machine with compliance gatekeeping.
     */
    public function transitionStatus(SalesOrder $order, string $newStatus, ?User $user = null, ?string $notes = null): SalesOrder
    {
        $validTransitions = [
            'draft' => ['pending_approval', 'cancelled'],
            'pending_approval' => ['approved', 'cancelled'],
            'approved' => ['allocated', 'cancelled'],
            'allocated' => ['dispatched', 'cancelled'],
            'dispatched' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => [],
        ];

        $currentStatus = $order->status;
        $allowed = $validTransitions[$currentStatus] ?? [];

        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException("Invalid order transition from '{$currentStatus}' to '{$newStatus}'.");
        }

        // Compliance Gatekeeper before Approval
        if ($newStatus === 'approved') {
            $customer = $order->customer;
            $extension = $customer->extension;
            if ($extension && $extension->credit_limit > 0) {
                if ($order->total_amount > $extension->credit_limit) {
                    throw new InvalidArgumentException("Order approval blocked: Total amount ({$order->total_amount}) exceeds credit limit ({$extension->credit_limit}).");
                }
            }
        }

        // Generate KRA ETIMS electronic tax receipt upon dispatch
        if ($newStatus === 'dispatched' && empty($order->etims_receipt_number)) {
            $etimsData = $this->kraEtimsAdapter->generateElectronicTaxReceipt($order);
            $order->update([
                'etims_receipt_number' => $etimsData['etims_receipt_number'],
                'etims_signature' => $etimsData['etims_signature'],
                'etims_qr_code' => $etimsData['etims_qr_code'],
            ]);
        }

        $order->update(['status' => $newStatus]);

        // Append immutable order event (OM-008)
        OrderEvent::create([
            'id' => (string) Str::uuid(),
            'sales_order_id' => $order->id,
            'user_id' => $user?->id,
            'from_status' => $currentStatus,
            'to_status' => $newStatus,
            'event_type' => 'STATUS_TRANSITION',
            'notes' => $notes ?? "Transitioned from {$currentStatus} to {$newStatus}.",
            'created_at' => now(),
        ]);

        return $order->fresh();
    }
}
