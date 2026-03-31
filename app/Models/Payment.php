<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'tenant_id',
        'order_id',
        'user_id',
        'method',
        'gateway',
        'status',
        'currency',
        'amount',
        'reference',
        'gateway_reference',
        'gateway_payment_id',
        'gateway_url',
        'bank_transfer_proof_path',
        'paid_at',
        'verified_at',
        'meta',
        'notes',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}