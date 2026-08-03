<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PromiseToPay extends Model
{
    use HasUuids;

    protected $table = 'promises_to_pay';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'sequence',
        'customer_id',
        'activity_id',
        'promised_amount',
        'promised_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'promised_amount' => 'float',
        'promised_date' => 'date',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }
}
