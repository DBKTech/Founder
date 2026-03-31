<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use App\Enums\OrderStatus;
use App\Models\Tenant;

class Order extends Model
{

    use BelongsToTenant;

    protected $guarded = [];

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

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class);
    }
    
    public function getSummaryItemsAttribute(): string
    {
        // make sure items loaded (OrderResource already eager loads items.product)
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        if ($items->isEmpty()) {
            return '-';
        }

        // Example output: "Sabun Maman x2, Serum Booster x1"
        return $items
            ->take(3)
            ->map(fn($i) => ($i->title ?? 'Item') . ' x' . (int) $i->qty)
            ->implode(', ')
            . ($items->count() > 3 ? '…' : '');
    }
}
