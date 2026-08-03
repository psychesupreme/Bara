<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommercialProduct extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'package_type',
        'unit_size',
        'moq',
        'is_returnable',
        'is_active',
    ];

    protected $casts = [
        'unit_size' => 'integer',
        'moq' => 'integer',
        'is_returnable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function priceRules()
    {
        return $this->hasMany(PriceRule::class, 'product_id');
    }
}
