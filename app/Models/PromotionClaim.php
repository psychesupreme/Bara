<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PromotionClaim extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'promotion_id',
        'sales_order_id',
        'customer_id',
        'claimed_amount',
        'evidence_uuid',
        'status',
    ];

    protected $casts = [
        'claimed_amount' => 'float',
    ];
}
