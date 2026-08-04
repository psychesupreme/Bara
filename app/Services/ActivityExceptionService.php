<?php

namespace App\Services;

use App\Events\ExceptionRaisedEvent;
use App\Events\ExceptionResolvedEvent;
use App\Models\Activity;
use App\Models\ActivityException;
use App\Models\CustomerOutletExtension;
use App\Models\OrderEvent;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\VerificationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityExceptionService
{
    /**
     * Route failed presence/evidence attempts to supervisory exception queue.
     */
    public function routeToException(
        Activity $activity,
        User $user,
        string $exceptionType,
        string $reason
    ): ActivityException {
        $activity->update([
            'status' => 'exception',
        ]);

        $code = 'EXP-' . strtoupper(substr($exceptionType, 0, 4)) . '-' . rand(100, 999);

        $exception = ActivityException::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'exception_type' => $exceptionType,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        // Broadcast real-time exception alert over Reverb WebSockets
        event(new ExceptionRaisedEvent(
            exceptionId: (string) $exception->id,
            code: $code,
            exceptionType: $exceptionType,
            repName: $user->name,
            customerName: $activity->fieldLocation ? $activity->fieldLocation->name : 'Nairobi Outlet',
            reason: $reason,
            severity: in_array($exceptionType, ['credit_override', 'credit']) ? 'high' : 'medium',
            timestamp: now()->toTimeString()
        ));

        return $exception;
    }

    /**
     * Supervisor approves exception request.
     * Executes real DB state mutations on Activity, SalesOrder, Customer Credit Ledger, and logs append-only audit events.
     */
    public function approveException(ActivityException $exception, User $reviewer, string $notes): ActivityException
    {
        DB::transaction(function () use ($exception, $reviewer, $notes) {
            $exception->update([
                'reviewer_id' => $reviewer->id,
                'status' => 'approved',
                'review_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            // Update Activity state
            $newActivityStatus = in_array($exception->exception_type, ['off_geofence', 'geofence', 'geofence_override']) ? 'checked_in' : 'approved';
            if ($exception->activity) {
                $exception->activity->update([
                    'status' => $newActivityStatus,
                ]);

                // Update verification event if present (verification_status column)
                VerificationEvent::where('activity_id', $exception->activity_id)
                    ->update([
                        'verification_status' => 'overridden',
                        'exception_reason' => 'Supervisor override approved by ' . $reviewer->name . ': ' . $notes,
                    ]);
            }

            // If credit override exception, mutate linked SalesOrder state machine & customer credit balance
            if (in_array($exception->exception_type, ['credit_override', 'credit'])) {
                $order = SalesOrder::where('activity_id', $exception->activity_id)
                    ->orWhere('customer_id', optional($exception->activity)->customer_id)
                    ->whereIn('status', ['pending_approval', 'credit_hold', 'draft'])
                    ->latest()
                    ->first();

                if ($order) {
                    $order->update([
                        'status' => 'approved',
                    ]);

                    // Append order audit event
                    OrderEvent::create([
                        'sales_order_id' => $order->id,
                        'event_type' => 'credit_override_approved',
                        'performed_by' => $reviewer->id,
                        'notes' => 'Credit override approved: ' . $notes,
                    ]);

                    // Update Customer Credit Ledger
                    $extension = CustomerOutletExtension::where('customer_id', $order->customer_id)->first();
                    if ($extension) {
                        $extension->increment('outstanding_balance', $order->total_amount);
                    }
                }
            }

            // Broadcast ExceptionResolvedEvent over Reverb WebSockets
            $code = 'EXP-' . strtoupper(substr($exception->exception_type, 0, 4)) . '-' . $exception->id;
            event(new ExceptionResolvedEvent(
                exceptionId: (string) $exception->id,
                code: $code,
                status: 'approved',
                reviewerName: $reviewer->name,
                notes: $notes,
                timestamp: now()->toTimeString()
            ));
        });

        return $exception->fresh();
    }

    /**
     * Supervisor rejects exception request.
     */
    public function rejectException(ActivityException $exception, User $reviewer, string $notes): ActivityException
    {
        DB::transaction(function () use ($exception, $reviewer, $notes) {
            $exception->update([
                'reviewer_id' => $reviewer->id,
                'status' => 'rejected',
                'review_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            if ($exception->activity) {
                $exception->activity->update([
                    'status' => 'rejected',
                ]);
            }

            // If credit override rejected, set linked SalesOrder to credit_hold / rejected
            if (in_array($exception->exception_type, ['credit_override', 'credit'])) {
                $order = SalesOrder::where('activity_id', $exception->activity_id)
                    ->orWhere('customer_id', optional($exception->activity)->customer_id)
                    ->whereIn('status', ['pending_approval', 'draft'])
                    ->latest()
                    ->first();

                if ($order) {
                    $order->update([
                        'status' => 'rejected',
                    ]);

                    OrderEvent::create([
                        'sales_order_id' => $order->id,
                        'event_type' => 'credit_override_rejected',
                        'performed_by' => $reviewer->id,
                        'notes' => 'Credit override rejected: ' . $notes,
                    ]);
                }
            }

            // Broadcast ExceptionResolvedEvent over Reverb WebSockets
            $code = 'EXP-' . strtoupper(substr($exception->exception_type, 0, 4)) . '-' . $exception->id;
            event(new ExceptionResolvedEvent(
                exceptionId: (string) $exception->id,
                code: $code,
                status: 'rejected',
                reviewerName: $reviewer->name,
                notes: $notes,
                timestamp: now()->toTimeString()
            ));
        });

        return $exception->fresh();
    }
}
