<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use App\Enums\OrderStatus;

class Order extends Model
{

    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'order_no',
        'status',
        'total',
        'ordered_at',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'status' => OrderStatus::class,
    ];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function shipment()
    {
        return $this->hasOne(\App\Models\Shipment::class);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\OrderItem::class);
    }

    public function placedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'placed_by_user_id');
    }
}
