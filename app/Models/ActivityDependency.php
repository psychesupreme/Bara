<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ActivityDependency extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'activity_id',
        'prerequisite_activity_id',
        'dependency_type',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function prerequisite()
    {
        return $this->belongsTo(Activity::class, 'prerequisite_activity_id');
    }
}
