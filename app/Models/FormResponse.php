<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'form_version_id',
        'activity_id',
        'respondent_id',
        'response_data',
        'score',
        'submission_latitude',
        'submission_longitude',
        'submitted_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'score' => 'float',
        'submission_latitude' => 'float',
        'submission_longitude' => 'float',
        'submitted_at' => 'datetime',
    ];

    public function formVersion()
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function respondent()
    {
        return $this->belongsTo(User::class, 'respondent_id');
    }
}
