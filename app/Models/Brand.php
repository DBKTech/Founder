<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'slug', 'logo_path', 'visible_to_user_types'];

    protected $casts = [
        'visible_to_user_types' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($brand) {
            $base = $brand->slug;
            $slug = $base;
            $i = 1;

            while (
                static::where('tenant_id', $brand->tenant_id)
                    ->where('slug', $slug)
                    ->exists()
            ) {
                $slug = $base . '-' . $i++;
            }

            $brand->slug = $slug;
        });
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
