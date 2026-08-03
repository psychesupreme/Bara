<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SystemNotice extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'message',
        'target_role',
        'target_commercial_node_id',
        'is_mandatory',
        'expires_at',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function acknowledgments()
    {
        return $this->hasMany(UserNoticeAcknowledgment::class, 'system_notice_id');
    }
}
