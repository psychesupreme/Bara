<?php

namespace App\Services;

use App\Models\Collection;
use InvalidArgumentException;

class PaymentProcessingService
{
    /**
     * Standard exchange rates relative to KES base currency.
     */
    protected array $exchangeRates = [
        'KES' => 1.0000,
        'USD' => 130.0000,
        'UGX' => 0.0350,
        'TZS' => 0.0500,
        'NGN' => 0.0850,
    ];

    /**
     * Convert currency amount to base KES amount.
     */
    public function convertToBaseAmount(float $amount, string $currency): array
    {
        $rate = $this->exchangeRates[strtoupper($currency)] ?? 1.0000;
        $baseAmount = round($amount * $rate, 2);

        return [
            'currency' => strtoupper($currency),
            'exchange_rate' => $rate,
            'amount' => $amount,
            'base_amount' => $baseAmount,
        ];
    }

    /**
     * Validate payment capture rules (Offline STK Push prohibition & Gateway Idempotency).
     */
    public function validatePaymentCapture(string $paymentMode, bool $isOffline, ?string $gatewayReference = null): void
    {
        // Rule 80: Disable offline capture for real-time gateway confirmation (M-Pesa STK)
        if ($paymentMode === 'mpesa_stk' && $isOffline) {
            throw new InvalidArgumentException("Offline capture is prohibited for M-Pesa STK Push payment mode (Rule 80).");
        }

        // Rule 77: Duplicate transaction reference or gateway confirmation check
        if (!empty($gatewayReference)) {
            $duplicate = Collection::where('gateway_reference', $gatewayReference)->exists();
            if ($duplicate) {
                throw new InvalidArgumentException("Duplicate gateway reference '{$gatewayReference}' detected and blocked (Rule 77).");
            }
        }
    }
}
