<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class Integration extends Model
{
    protected $fillable = [
        'tenant_id',
        'platform',
        'store_name',
        'store_url',
        'api_key',
        'api_secret',
        'webhook_secret',
        'status',
        'last_tested_at',
        'last_synced_at',
        'meta',
    ];

    protected $casts = [
        'last_tested_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'meta' => 'array',
    ];

    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function apiSecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function logs()
    {
        return $this->hasMany(IntegrationLog::class);
    }
}