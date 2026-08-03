<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FormVersion extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'form_template_id',
        'version_number',
        'schema_definition',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'schema_definition' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function formTemplate()
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function responses()
    {
        return $this->hasMany(FormResponse::class, 'form_version_id');
    }
}
