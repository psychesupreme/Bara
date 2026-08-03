<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'asset_code',
        'name',
        'asset_type',
        'serial_number',
        'status',
    ];

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id')->latest();
    }
}
