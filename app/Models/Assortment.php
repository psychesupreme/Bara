<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Assortment extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'channel',
        'commercial_node_id',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function commercialNode()
    {
        return $this->belongsTo(CommercialNode::class, 'commercial_node_id');
    }
}
