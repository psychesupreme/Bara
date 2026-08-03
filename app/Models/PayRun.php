<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PayRun extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'period_start',
        'period_end',
        'status',
        'initiated_by',
        'approved_by',
        'total_gross_pay',
        'total_statutory_deductions',
        'total_reimbursements',
        'total_net_pay',
        'currency',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_gross_pay' => 'float',
        'total_statutory_deductions' => 'float',
        'total_reimbursements' => 'float',
        'total_net_pay' => 'float',
    ];

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PayRunItem::class, 'pay_run_id');
    }
}
