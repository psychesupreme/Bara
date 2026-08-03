<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PayRunItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'pay_run_id',
        'user_id',
        'base_pay',
        'overtime_pay',
        'commission_pay',
        'gross_pay',
        'paye_tax',
        'nssf_deduction',
        'shif_deduction',
        'expense_reimbursement',
        'net_pay',
        'payslip_number',
    ];

    protected $casts = [
        'base_pay' => 'float',
        'overtime_pay' => 'float',
        'commission_pay' => 'float',
        'gross_pay' => 'float',
        'paye_tax' => 'float',
        'nssf_deduction' => 'float',
        'shif_deduction' => 'float',
        'expense_reimbursement' => 'float',
        'net_pay' => 'float',
    ];

    public function payRun()
    {
        return $this->belongsTo(PayRun::class, 'pay_run_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
