<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Collection;
use App\Models\CollectionAllocation;
use App\Models\CollectionReconciliation;
use App\Models\CollectionReversal;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CollectionService
{
    public function __construct(
        protected PaymentProcessingService $paymentProcessingService
    ) {}

    /**
     * Capture customer collection payment against invoices.
     */
    public function captureCollection(
        User $collector,
        string $customerId,
        float $amount,
        string $paymentMode = 'cash',
        string $currency = 'KES',
        ?Activity $activity = null,
        ?string $gatewayReference = null,
        bool $isOfflineCaptured = false,
        ?array $invoiceAllocations = null
    ): Collection {
        $this->paymentProcessingService->validatePaymentCapture($paymentMode, $isOfflineCaptured, $gatewayReference);
        $converted = $this->paymentProcessingService->convertToBaseAmount($amount, $currency);

        return DB::transaction(function () use (
            $collector, $customerId, $amount, $paymentMode, $currency,
            $activity, $gatewayReference, $isOfflineCaptured, $invoiceAllocations, $converted
        ) {
            $receiptNumber = 'RCT-' . date('Y') . '-' . Str::upper(Str::random(6));

            $collection = Collection::create([
                'client_uuid' => (string) Str::uuid(),
                'sequence' => 1,
                'receipt_number' => $receiptNumber,
                'collector_id' => $collector->id,
                'customer_id' => $customerId,
                'activity_id' => $activity?->id,
                'payment_mode' => $paymentMode,
                'currency' => $converted['currency'],
                'exchange_rate' => $converted['exchange_rate'],
                'amount' => $amount,
                'base_amount' => $converted['base_amount'],
                'gateway_reference' => $gatewayReference,
                'status' => 'confirmed',
                'is_offline_captured' => $isOfflineCaptured,
                'captured_at' => now(),
            ]);

            // Allocate payment to invoices if provided
            if (!empty($invoiceAllocations)) {
                foreach ($invoiceAllocations as $alloc) {
                    $invoice = Invoice::find($alloc['invoice_id']);
                    if ($invoice) {
                        $allocatedAmount = min((float) $alloc['allocated_amount'], (float) $invoice->balance_amount);
                        
                        CollectionAllocation::create([
                            'collection_id' => $collection->id,
                            'invoice_id' => $invoice->id,
                            'allocated_amount' => $allocatedAmount,
                        ]);

                        $newPaid = $invoice->paid_amount + $allocatedAmount;
                        $newBalance = max(0.0, $invoice->total_amount - $newPaid);
                        $status = ($newBalance <= 0) ? 'paid' : 'partial';

                        $invoice->update([
                            'paid_amount' => $newPaid,
                            'balance_amount' => $newBalance,
                            'status' => $status,
                        ]);
                    }
                }
            }

            return $collection;
        });
    }

    /**
     * Reconcile collection payment enforcing Segregation of Duties (Rule 75).
     */
    public function reconcileCollection(Collection $collection, User $reconciler, ?string $notes = null): CollectionReconciliation
    {
        // Rule 75: Segregation of Duties — collector cannot reconcile own payment
        if ($collection->collector_id === $reconciler->id) {
            throw new InvalidArgumentException("Segregation of Duties Violation: The user who collected the payment cannot reconcile it (Rule 75).");
        }

        return DB::transaction(function () use ($collection, $reconciler, $notes) {
            $reconciliation = CollectionReconciliation::create([
                'collection_id' => $collection->id,
                'reconciled_by' => $reconciler->id,
                'reconciled_at' => now(),
                'status' => 'reconciled',
                'notes' => $notes,
            ]);

            $collection->update(['status' => 'reconciled']);

            return $reconciliation;
        });
    }

    /**
     * Reverse a posted collection cleanly by creating a compensating reversal record without deleting (Rule 76).
     */
    public function reverseCollection(Collection $collection, User $reversedBy, string $reason): CollectionReversal
    {
        return DB::transaction(function () use ($collection, $reversedBy, $reason) {
            // Rule 76: Posted collections cannot be edited or deleted; requires compensating entries
            $reversalReceipt = 'REV-' . date('Y') . '-' . Str::upper(Str::random(6));

            $reversal = CollectionReversal::create([
                'collection_id' => $collection->id,
                'reversed_by' => $reversedBy->id,
                'reversal_receipt_number' => $reversalReceipt,
                'reason' => $reason,
                'reversed_at' => now(),
            ]);

            // Revert allocated invoice balances
            foreach ($collection->allocations as $alloc) {
                $invoice = $alloc->invoice;
                if ($invoice) {
                    $newPaid = max(0.0, $invoice->paid_amount - $alloc->allocated_amount);
                    $newBalance = $invoice->total_amount - $newPaid;
                    $status = ($newPaid <= 0) ? 'unpaid' : 'partial';

                    $invoice->update([
                        'paid_amount' => $newPaid,
                        'balance_amount' => $newBalance,
                        'status' => $status,
                    ]);
                }
            }

            $collection->update(['status' => 'reversed']);

            return $reversal;
        });
    }
}
