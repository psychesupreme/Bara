<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'reference_no',
        'activity_type',
        'category',
        'title',
        'description',
        'customer_id',
        'field_location_id',
        'parent_activity_id',
        'planned_start_at',
        'planned_end_at',
        'due_at',
        'timezone',
        'allowed_execution_window_minutes',
        'priority',
        'status',
        'location_policy',
        'attendance_policy',
        'evidence_policy',
        'approval_policy',
        'actual_start_at',
        'actual_end_at',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'device_id',
        'is_offline_captured',
        'completion_notes',
        'outcome_code',
        'payload',
    ];

    protected $casts = [
        'planned_start_at' => 'datetime',
        'planned_end_at' => 'datetime',
        'due_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'start_latitude' => 'decimal:7',
        'start_longitude' => 'decimal:7',
        'end_latitude' => 'decimal:7',
        'end_longitude' => 'decimal:7',
        'is_offline_captured' => 'boolean',
        'payload' => 'array',
    ];

    public function fieldLocation()
    {
        return $this->belongsTo(FieldLocation::class, 'field_location_id');
    }

    public function assignments()
    {
        return $this->hasMany(ActivityAssignment::class, 'activity_id');
    }

    public function evidence()
    {
        return $this->hasMany(ActivityEvidence::class, 'activity_id');
    }

    public function events()
    {
        return $this->hasMany(ActivityEvent::class, 'activity_id');
    }

    public function verificationEvents()
    {
        return $this->hasMany(VerificationEvent::class, 'activity_id');
    }
}
