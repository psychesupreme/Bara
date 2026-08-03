<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'route_plan_id',
        'customer_id',
        'stop_order',
        'guided_call_steps',
    ];

    protected $casts = [
        'stop_order' => 'integer',
        'guided_call_steps' => 'array',
    ];

    public function routePlan()
    {
        return $this->belongsTo(RoutePlan::class, 'route_plan_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
