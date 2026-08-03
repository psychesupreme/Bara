<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'holiday_date',
        'country_code',
        'multiplier',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'multiplier' => 'float',
    ];
}
