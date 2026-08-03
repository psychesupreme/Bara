<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserCommercialScope extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'commercial_node_id',
        'include_descendants',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'include_descendants' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function commercialNode()
    {
        return $this->belongsTo(CommercialNode::class, 'commercial_node_id');
    }
}
