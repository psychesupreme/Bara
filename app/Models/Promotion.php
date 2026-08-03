<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'promo_type',
        'discount_percentage',
        'buy_product_id',
        'buy_quantity',
        'get_product_id',
        'get_quantity',
        'budget_cap',
        'spent_amount',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'float',
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'budget_cap' => 'float',
        'spent_amount' => 'float',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];
}
