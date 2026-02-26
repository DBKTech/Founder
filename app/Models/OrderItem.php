<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','order_id','product_id','title','sku','qty','unit_price','line_total'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}