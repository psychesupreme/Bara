<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'order_number',
        'customer_id',
        'sales_rep_id',
        'activity_id',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'etims_receipt_number',
        'etims_qr_code',
        'etims_signature',
        'is_offline_captured',
    ];

    protected $casts = [
        'subtotal_amount' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'is_offline_captured' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function salesRep()
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function lines()
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id');
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class, 'sales_order_id')->latest();
    }
}
