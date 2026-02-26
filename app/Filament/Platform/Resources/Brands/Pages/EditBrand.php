<?php

namespace App\Filament\Platform\Resources\Brands\Pages;

use App\Filament\Platform\Resources\Brands\BrandResource;
use App\Support\TenantContext;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['tenant_id'] = TenantContext::id();
        return $data;
    }
}