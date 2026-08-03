<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ShiftConfiguration extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'shift_type',
        'start_time',
        'end_time',
        'grace_period_minutes',
        'geofence_id',
        'is_active',
    ];

    protected $casts = [
        'grace_period_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function geofence()
    {
        return $this->belongsTo(FieldLocation::class, 'geofence_id');
    }
}
