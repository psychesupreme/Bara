<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MerchObservationLine extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'merch_observation_id',
        'product_id',
        'is_in_stock',
        'facing_count',
        'shelf_price',
    ];

    protected $casts = [
        'is_in_stock' => 'boolean',
        'facing_count' => 'integer',
        'shelf_price' => 'float',
    ];
}
