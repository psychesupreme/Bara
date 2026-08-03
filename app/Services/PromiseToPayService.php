<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\PromiseToPay;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PromiseToPayService
{
    public function __construct(
        protected FollowUpAutomationService $followUpAutomationService
    ) {}

    /**
     * Record a customer "Promise to Pay" commitment and trigger automated follow-up activity (CL-009).
     */
    public function recordPromise(
        string $customerId,
        float $promisedAmount,
        Carbon $promisedDate,
        ?Activity $activity = null,
        ?string $notes = null
    ): PromiseToPay {
        $promise = PromiseToPay::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'customer_id' => $customerId,
            'activity_id' => $activity?->id,
            'promised_amount' => $promisedAmount,
            'promised_date' => $promisedDate,
            'status' => 'pending',
            'notes' => $notes,
        ]);

        if ($activity) {
            $activity->update(['outcome_code' => 'PROMISE_TO_PAY']);
            $this->followUpAutomationService->evaluateAndScheduleFollowUp($activity);
        }

        return $promise;
    }
}
