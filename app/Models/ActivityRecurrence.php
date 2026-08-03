<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ActivityRecurrence extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'parent_activity_id',
        'recurrence_pattern',
        'interval',
        'days_of_week',
        'end_date',
        'last_generated_at',
    ];

    protected $casts = [
        'interval' => 'integer',
        'days_of_week' => 'array',
        'end_date' => 'date',
        'last_generated_at' => 'datetime',
    ];

    public function parentActivity()
    {
        return $this->belongsTo(Activity::class, 'parent_activity_id');
    }
}
