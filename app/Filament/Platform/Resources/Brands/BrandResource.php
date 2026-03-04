<?php

namespace App\Filament\Platform\Resources\Brands;

use App\Filament\Platform\Resources\Brands\Pages\CreateBrand;
use App\Filament\Platform\Resources\Brands\Pages\EditBrand;
use App\Filament\Platform\Resources\Brands\Pages\ListBrands;
use App\Filament\Platform\Resources\Brands\Schemas\BrandForm;
use App\Filament\Platform\Resources\Brands\Tables\BrandsTable;
use App\Models\Brand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static UnitEnum|string|null $navigationGroup = 'Marketplace';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Brands';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}