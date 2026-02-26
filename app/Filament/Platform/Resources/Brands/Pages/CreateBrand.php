<?php

namespace App\Filament\Platform\Resources\Brands\Pages;

use App\Filament\Platform\Resources\Brands\BrandResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}