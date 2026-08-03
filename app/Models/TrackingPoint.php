<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TrackingPoint extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'session_id',
        'latitude',
        'longitude',
        'accuracy_meters',
        'speed_mps',
        'heading_degrees',
        'recorded_at',
        'received_at',
        'is_mock_location',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_meters' => 'float',
        'speed_mps' => 'float',
        'heading_degrees' => 'float',
        'recorded_at' => 'datetime',
        'received_at' => 'datetime',
        'is_mock_location' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(TrackingSession::class, 'session_id');
    }
}
