<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExpensePolicy extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'category',
        'max_claim_amount',
        'receipt_required_above',
    ];

    protected $casts = [
        'max_claim_amount' => 'float',
        'receipt_required_above' => 'float',
    ];
}
