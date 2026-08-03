<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'name',
        'code',
        'customer_type',
        'parent_id',
        'commercial_node_id',
        'tax_number',
        'phone',
        'email',
        'address',
        'county',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Customer::class, 'parent_id');
    }

    public function outlets()
    {
        return $this->hasMany(Customer::class, 'parent_id');
    }

    public function extension()
    {
        return $this->hasOne(CustomerOutletExtension::class, 'customer_id');
    }

    public function commercialNode()
    {
        return $this->belongsTo(CommercialNode::class, 'commercial_node_id');
    }
}
