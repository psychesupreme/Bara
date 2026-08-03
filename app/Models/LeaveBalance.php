<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'leave_type',
        'balance_days',
        'accrued_days',
        'allow_negative_balance',
    ];

    protected $casts = [
        'balance_days' => 'float',
        'accrued_days' => 'float',
        'allow_negative_balance' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
