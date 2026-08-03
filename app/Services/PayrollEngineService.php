<?php

namespace App\Services;

use App\Models\CompensationProfile;
use App\Models\ExpenseClaim;
use App\Models\PayRun;
use App\Models\PayRunItem;
use App\Models\SalesOrder;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PayrollEngineService
{
    public function __construct(
        protected KraPayeTaxAdapter $taxAdapter
    ) {}

    /**
     * Calculate Pay Run for a given period across active users.
     */
    public function calculatePayRun(User $initiator, Carbon $periodStart, Carbon $periodEnd): PayRun
    {
        return DB::transaction(function () use ($initiator, $periodStart, $periodEnd) {
            $payRun = PayRun::create([
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => 'calculated',
                'initiated_by' => $initiator->id,
                'currency' => 'KES',
            ]);

            $users = User::all();
            $totalGross = 0.0;
            $totalStatutory = 0.0;
            $totalReimbursements = 0.0;
            $totalNet = 0.0;

            foreach ($users as $user) {
                $compProfile = CompensationProfile::where('user_id', $user->id)->first();
                $baseRate = $compProfile ? $compProfile->base_rate : 30000.00;

                // 1. Base Pay & Overtime from Approved Timesheets (Phase 4)
                $timesheets = Timesheet::where('user_id', $user->id)
                    ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->where('status', 'approved')
                    ->get();

                $regularHours = $timesheets->sum('regular_hours');
                $overtimeHours = $timesheets->sum('overtime_hours');
                $holidayHours = $timesheets->sum('holiday_overtime_hours');

                $hourlyRate = round($baseRate / 160.0, 2); // 160 std hours/month
                $basePay = round($regularHours * $hourlyRate, 2);
                if ($basePay <= 0) {
                    $basePay = $baseRate; // Salary fallback
                }

                $overtimePay = round(($overtimeHours * $hourlyRate * 1.5) + ($holidayHours * $hourlyRate * 2.0), 2);

                // 2. Sales Commission from Delivered Paid Orders (Phase 7)
                $salesOrders = SalesOrder::where('sales_rep_id', $user->id)
                    ->where('status', 'delivered')
                    ->whereBetween('updated_at', [$periodStart, $periodEnd])
                    ->get();

                $commissionPay = round($salesOrders->sum('total_amount') * 0.05, 2); // 5% commission rate

                $grossPay = round($basePay + $overtimePay + $commissionPay, 2);

                // 3. Statutory Deductions via KRA Tax Adapter
                $taxResult = $this->taxAdapter->calculateDeductions($grossPay);

                // 4. Expense Reimbursements (Rule 197)
                $approvedExpenses = ExpenseClaim::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->whereNull('pay_run_id')
                    ->get();

                $reimbursementAmount = $approvedExpenses->sum('amount');

                // Link expenses to this pay run
                foreach ($approvedExpenses as $exp) {
                    $exp->update(['pay_run_id' => $payRun->id]);
                }

                $netPay = max(0.0, round(($grossPay - $taxResult['total_statutory']) + $reimbursementAmount, 2));

                $payslipNo = 'PS-' . date('Ym') . '-' . Str::upper(Str::random(6));

                PayRunItem::create([
                    'pay_run_id' => $payRun->id,
                    'user_id' => $user->id,
                    'base_pay' => $basePay,
                    'overtime_pay' => $overtimePay,
                    'commission_pay' => $commissionPay,
                    'gross_pay' => $grossPay,
                    'paye_tax' => $taxResult['paye_tax'],
                    'nssf_deduction' => $taxResult['nssf_deduction'],
                    'shif_deduction' => $taxResult['shif_deduction'],
                    'expense_reimbursement' => $reimbursementAmount,
                    'net_pay' => $netPay,
                    'payslip_number' => $payslipNo,
                ]);

                $totalGross += $grossPay;
                $totalStatutory += $taxResult['total_statutory'];
                $totalReimbursements += $reimbursementAmount;
                $totalNet += $netPay;
            }

            $payRun->update([
                'total_gross_pay' => $totalGross,
                'total_statutory_deductions' => $totalStatutory,
                'total_reimbursements' => $totalReimbursements,
                'total_net_pay' => $totalNet,
            ]);

            return $payRun->load('items');
        });
    }

    /**
     * Transition Pay Run to reviewed status and lock underlying timesheets (Rule 179).
     */
    public function reviewPayRun(PayRun $payRun): PayRun
    {
        $payRun->update(['status' => 'reviewed']);

        // Lock all timesheets in the pay run period (Rule 179)
        Timesheet::whereBetween('date', [$payRun->period_start, $payRun->period_end])
            ->update(['is_locked' => true]);

        return $payRun;
    }

    /**
     * Approve Pay Run enforcing Segregation of Duties (Rule 181).
     */
    public function approvePayRun(PayRun $payRun, User $approver): PayRun
    {
        // Rule 181: Segregation of Duties — initiator cannot approve pay run
        if ($payRun->initiated_by === $approver->id) {
            throw new InvalidArgumentException("Segregation of Duties Violation: The user who calculated the Pay Run cannot approve it (Rule 181).");
        }

        $payRun->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
        ]);

        return $payRun;
    }

    /**
     * Disburse Pay Run and mark underlying expenses as reimbursed.
     */
    public function disbursePayRun(PayRun $payRun): PayRun
    {
        $payRun->update(['status' => 'disbursed']);

        ExpenseClaim::where('pay_run_id', $payRun->id)
            ->update(['status' => 'reimbursed']);

        return $payRun;
    }
}
