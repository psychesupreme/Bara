<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DatabaseBackup extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'filename',
        'checksum_sha256',
        'size_bytes',
        'status',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];
}
