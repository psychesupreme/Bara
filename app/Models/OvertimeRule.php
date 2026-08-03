<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OvertimeRule extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'daily_threshold_hours',
        'weekly_threshold_hours',
        'standard_multiplier',
        'holiday_multiplier',
    ];

    protected $casts = [
        'daily_threshold_hours' => 'float',
        'weekly_threshold_hours' => 'float',
        'standard_multiplier' => 'float',
        'holiday_multiplier' => 'float',
    ];
}
