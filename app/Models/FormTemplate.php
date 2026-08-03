<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormTemplate extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'title',
        'code',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function versions()
    {
        return $this->hasMany(FormVersion::class, 'form_template_id');
    }

    public function latestPublishedVersion()
    {
        return $this->hasOne(FormVersion::class, 'form_template_id')
            ->where('is_published', true)
            ->latestOfMany('version_number');
    }
}
