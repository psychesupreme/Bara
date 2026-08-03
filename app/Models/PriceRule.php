<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PriceRule extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'product_id',
        'level_type',
        'level_id',
        'unit_price',
        'currency',
        'priority',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'priority' => 'integer',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(CommercialProduct::class, 'product_id');
    }
}
