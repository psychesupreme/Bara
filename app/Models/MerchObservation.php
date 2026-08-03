<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MerchObservation extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'customer_id',
        'activity_id',
        'user_id',
        'msl_compliance_score',
        'share_of_shelf_percentage',
        'evidence_photo_url',
        'posm_condition',
        'notes',
    ];

    protected $casts = [
        'msl_compliance_score' => 'float',
        'share_of_shelf_percentage' => 'float',
    ];

    public function lines()
    {
        return $this->hasMany(MerchObservationLine::class, 'merch_observation_id');
    }

    public function competitorProducts()
    {
        return $this->hasMany(CompetitorProduct::class, 'merch_observation_id');
    }
}
