<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExpenseClaim extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'claim_number',
        'user_id',
        'activity_id',
        'pay_run_id',
        'category',
        'merchant',
        'amount',
        'receipt_url',
        'status',
        'is_offline_captured',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_offline_captured' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
