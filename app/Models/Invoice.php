<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'currency',
        'status',
        'due_date',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'balance_amount' => 'float',
        'due_date' => 'date',
    ];

    public function allocations()
    {
        return $this->hasMany(CollectionAllocation::class, 'invoice_id');
    }
}
