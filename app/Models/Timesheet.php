<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'user_id',
        'shift_configuration_id',
        'date',
        'clock_in_at',
        'clock_out_at',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'regular_hours',
        'overtime_hours',
        'holiday_overtime_hours',
        'is_late',
        'is_early_departure',
        'status',
        'reviewer_id',
        'review_notes',
        'is_locked',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'clock_in_latitude' => 'float',
        'clock_in_longitude' => 'float',
        'clock_out_latitude' => 'float',
        'clock_out_longitude' => 'float',
        'regular_hours' => 'float',
        'overtime_hours' => 'float',
        'holiday_overtime_hours' => 'float',
        'is_late' => 'boolean',
        'is_early_departure' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shiftConfiguration()
    {
        return $this->belongsTo(ShiftConfiguration::class, 'shift_configuration_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
