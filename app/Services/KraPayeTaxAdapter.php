<?php

namespace App\Services;

class KraPayeTaxAdapter
{
    /**
     * Calculate Kenya KRA PAYE tax, NSSF, and SHIF deductions for a gross salary.
     */
    public function calculateDeductions(float $grossPay): array
    {
        if ($grossPay <= 0) {
            return [
                'paye_tax' => 0.0,
                'nssf_deduction' => 0.0,
                'shif_deduction' => 0.0,
                'total_statutory' => 0.0,
            ];
        }

        // 1. NSSF Calculation (Tier 1 + Tier 2 = KES 2,160 max)
        $nssfDeduction = min(2160.0, round($grossPay * 0.06, 2));

        // Taxable Pay = Gross Pay - NSSF
        $taxablePay = max(0.0, $grossPay - $nssfDeduction);

        // 2. KRA PAYE Progressive Brackets (Monthly)
        // 10% on first 24,000
        // 25% on next 8,333 (24,001 to 32,333)
        // 30% on remainder above 32,333
        $taxable = $taxablePay;
        $payeBeforeRelief = 0.0;

        if ($taxable > 0) {
            $b1 = min($taxable, 24000.0);
            $payeBeforeRelief += $b1 * 0.10;
            $taxable -= $b1;
        }

        if ($taxable > 0) {
            $b2 = min($taxable, 8333.0);
            $payeBeforeRelief += $b2 * 0.25;
            $taxable -= $b2;
        }

        if ($taxable > 0) {
            $payeBeforeRelief += $taxable * 0.30;
        }

        // Apply Personal Relief KES 2,400/month
        $personalRelief = 2400.0;
        $payeTax = max(0.0, round($payeBeforeRelief - $personalRelief, 2));

        // 3. SHIF (Social Health Authority) = 2.75% of Gross Pay
        $shifDeduction = round($grossPay * 0.0275, 2);

        $totalStatutory = round($payeTax + $nssfDeduction + $shifDeduction, 2);

        return [
            'paye_tax' => $payeTax,
            'nssf_deduction' => $nssfDeduction,
            'shif_deduction' => $shifDeduction,
            'total_statutory' => $totalStatutory,
        ];
    }
}
