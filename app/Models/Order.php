<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

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

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function shipment()
    {
        return $this->hasOne(\App\Models\Shipment::class);
    }
}
