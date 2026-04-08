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

    protected $fillable = [
        'tenant_id',
        'source',
        'external_id',
        'customer_id',
        'order_no',
        'status',
        'currency',
        'subtotal',
        'discount_total',
        'shipping_total',
        'tax_total',
        'total',
        'ordered_at',
        'payment_method',
        'payment_status',
        'payment_gateway',
        'payment_ref',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'ordered_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
        'status' => OrderStatus::class,
    ];

    protected $appends = [
        'summary_items',
        'display_customer_name',
        'display_customer_phone',
        'display_customer_address',
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
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        if ($items->isEmpty()) {
            return '-';
        }

        return $items
            ->take(3)
            ->map(fn ($i) => ($i->title ?? 'Item') . ' x' . (int) $i->qty)
            ->implode(', ')
            . ($items->count() > 3 ? '…' : '');
    }

    public function getDisplayCustomerNameAttribute(): string
    {
        $billing = data_get($this->meta, 'billing', []);

        $fullName = trim(collect([
            data_get($billing, 'first_name'),
            data_get($billing, 'last_name'),
        ])->filter()->implode(' '));

        if ($fullName !== '') {
            return $fullName;
        }

        $company = trim((string) data_get($billing, 'company', ''));

        if ($company !== '') {
            return $company;
        }

        return $this->customer?->name ?? 'Walk-in Customer';
    }

    public function getDisplayCustomerPhoneAttribute(): string
    {
        $billingPhone = trim((string) data_get($this->meta, 'billing.phone', ''));

        if ($billingPhone !== '') {
            return $billingPhone;
        }

        return $this->customer?->phone ?? '-';
    }

    public function getDisplayCustomerAddressAttribute(): string
    {
        $billing = data_get($this->meta, 'billing', []);

        $parts = array_filter([
            trim((string) data_get($billing, 'address_1', '')),
            trim((string) data_get($billing, 'address_2', '')),
            trim((string) data_get($billing, 'city', '')),
            trim((string) data_get($billing, 'state', '')),
            trim((string) data_get($billing, 'postcode', '')),
            trim((string) data_get($billing, 'country', '')),
        ]);

        if (! empty($parts)) {
            return implode(', ', $parts);
        }

        return $this->customer?->address ?? '-';
    }
}