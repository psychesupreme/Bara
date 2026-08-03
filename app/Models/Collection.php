<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'receipt_number',
        'collector_id',
        'customer_id',
        'activity_id',
        'payment_mode',
        'currency',
        'exchange_rate',
        'amount',
        'base_amount',
        'gateway_reference',
        'status',
        'is_offline_captured',
        'captured_at',
    ];

    protected $casts = [
        'exchange_rate' => 'float',
        'amount' => 'float',
        'base_amount' => 'float',
        'is_offline_captured' => 'boolean',
        'captured_at' => 'datetime',
    ];

    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function allocations()
    {
        return $this->hasMany(CollectionAllocation::class, 'collection_id');
    }

    public function reconciliations()
    {
        return $this->hasMany(CollectionReconciliation::class, 'collection_id');
    }

    public function reversal()
    {
        return $this->hasOne(CollectionReversal::class, 'collection_id');
    }
}
