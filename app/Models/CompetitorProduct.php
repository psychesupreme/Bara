<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompetitorProduct extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'merch_observation_id',
        'competitor_name',
        'brand_name',
        'price',
        'promo_details',
    ];

    protected $casts = [
        'price' => 'float',
    ];
}
