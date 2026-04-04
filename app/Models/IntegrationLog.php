<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    protected $fillable = [
        'integration_id',
        'direction',
        'event_type',
        'request_url',
        'request_headers',
        'request_body',
        'response_code',
        'response_body',
        'status',
    ];

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }
}