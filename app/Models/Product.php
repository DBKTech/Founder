<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'brand_id',

        'name',
        'slug',
        'description',

        'status',
        'sku',

        'price',
        'compare_at_price',

        'product_type',
        'weight',
        'weight_unit',
        'max_units_per_purchase',

        'primary_image_path',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'max_units_per_purchase' => 'integer',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}