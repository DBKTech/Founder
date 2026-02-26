<?php

namespace App\Filament\Platform\Resources\Products\Pages;

use App\Filament\Platform\Resources\Products\ProductResource;
use App\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = TenantContext::id();
        return $data;
    }
}