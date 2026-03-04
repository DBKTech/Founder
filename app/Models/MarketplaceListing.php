<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceListing extends Model
{
    protected $fillable = [
        'tenant_id',
        'product_id',
        'status',
        'visibility',
        'published_at',
        'woo_sync_enabled',
        'woo_product_id',
        'woo_last_synced_at',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
