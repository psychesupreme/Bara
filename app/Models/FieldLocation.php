<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FieldLocation extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'name',
        'code',
        'location_type',
        'parent_id',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'address',
        'city',
        'county',
        'country_code',
        'opening_hours',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'geofence_radius_meters' => 'integer',
        'opening_hours' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(FieldLocation::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FieldLocation::class, 'parent_id');
    }
}
