<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VerificationEvent extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'activity_id',
        'user_id',
        'field_location_id',
        'verification_level',
        'verified_at',
        'latitude',
        'longitude',
        'gps_accuracy_meters',
        'distance_to_target_meters',
        'is_geofence_valid',
        'is_time_window_valid',
        'is_device_valid',
        'is_attendance_valid',
        'attendance_adapter',
        'device_id',
        'signature_hash',
        'verification_status',
        'exception_reason',
        'metadata',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'gps_accuracy_meters' => 'float',
        'distance_to_target_meters' => 'float',
        'is_geofence_valid' => 'boolean',
        'is_time_window_valid' => 'boolean',
        'is_device_valid' => 'boolean',
        'is_attendance_valid' => 'boolean',
        'metadata' => 'array',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fieldLocation()
    {
        return $this->belongsTo(FieldLocation::class, 'field_location_id');
    }
}
