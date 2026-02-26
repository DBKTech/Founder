<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Auto-fill tenant_id when creating
        static::creating(function (Model $model) {
            if (! $model->getAttribute('tenant_id') && TenantContext::id()) {
                $model->setAttribute('tenant_id', TenantContext::id());
            }
        });

        // Prevent tenant_id from being changed on update (safety)
        static::saving(function (Model $model) {
            if ($model->exists && $model->isDirty('tenant_id')) {
                $model->setAttribute('tenant_id', $model->getOriginal('tenant_id'));
            }
        });

        // Auto-scope queries by tenant_id
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenantId = TenantContext::id()) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    $tenantId
                );
            }
        });
    }
}