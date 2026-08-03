<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ActivityEvidence extends Model
{
    use HasUuids;

    protected $table = 'activity_evidence';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'activity_id',
        'evidence_type',
        'file_path',
        'mime_type',
        'file_size_bytes',
        'captured_latitude',
        'captured_longitude',
        'captured_at',
        'metadata',
    ];

    protected $casts = [
        'captured_latitude' => 'float',
        'captured_longitude' => 'float',
        'captured_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }
}
