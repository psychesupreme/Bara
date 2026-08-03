<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'commission_percentage',
        'min_sales_threshold',
        'is_active',
    ];

    protected $casts = [
        'commission_percentage' => 'float',
        'min_sales_threshold' => 'float',
        'is_active' => 'boolean',
    ];
}
