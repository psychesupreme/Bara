<?php

namespace App\Services;

use App\Models\ExpenseClaim;
use App\Models\ExpensePolicy;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ExpenseManagementService
{
    /**
     * Submit an expense claim with policy cap and mandatory receipt enforcement (Rules 195, 196).
     */
    public function submitClaim(
        User $user,
        string $category,
        string $merchant,
        float $amount,
        ?string $receiptUrl = null,
        ?string $activityId = null,
        bool $isOfflineCaptured = false
    ): ExpenseClaim {
        $policy = ExpensePolicy::where('category', strtolower($category))->first();

        if ($policy) {
            // Rule 195: Hard-block claims exceeding policy max cap
            if ($amount > $policy->max_claim_amount) {
                throw new InvalidArgumentException("Expense claim amount (KES {$amount}) exceeds category cap (KES {$policy->max_claim_amount}) for '{$category}' (Rule 195).");
            }

            // Rule 196: Mandatory receipt attachment for claims above policy threshold
            if ($policy->receipt_required_above > 0 && $amount > $policy->receipt_required_above && empty($receiptUrl)) {
                throw new InvalidArgumentException("Receipt image attachment is required for claims above KES {$policy->receipt_required_above} in category '{$category}' (Rule 196).");
            }
        }

        $claimNumber = 'EXP-' . date('Y') . '-' . Str::upper(Str::random(6));

        return ExpenseClaim::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'claim_number' => $claimNumber,
            'user_id' => $user->id,
            'activity_id' => $activityId,
            'category' => strtolower($category),
            'merchant' => $merchant,
            'amount' => $amount,
            'receipt_url' => $receiptUrl,
            'status' => 'pending',
            'is_offline_captured' => $isOfflineCaptured,
        ]);
    }

    /**
     * Approve expense claim.
     */
    public function approveClaim(ExpenseClaim $claim): ExpenseClaim
    {
        $claim->update(['status' => 'approved']);
        return $claim;
    }
}
