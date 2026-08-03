<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RoutePlan extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'sales_rep_id',
        'commercial_node_id',
        'visit_days',
        'is_active',
    ];

    protected $casts = [
        'visit_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function salesRep()
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function stops()
    {
        return $this->hasMany(RouteStop::class, 'route_plan_id')->orderBy('stop_order');
    }
}
