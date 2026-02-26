<?php

namespace App\Filament\Platform\Resources\Products\Pages;

use App\Filament\Platform\Resources\Products\ProductResource;
use App\Support\TenantContext;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['tenant_id'] = TenantContext::id();
        return $data;
    }
}