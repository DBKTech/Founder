<?php

namespace App\Filament\Platform\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('tenant_id')->dehydrated(false),

            Hidden::make('slug')
                ->dehydrated()
                ->required(),

            Grid::make(3)->schema([
                TextInput::make('name')
                    ->label('Product Name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('slug', Str::slug((string) $state));
                    }),

                Select::make('brand_id')
                    ->label('Brand Name')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('product_type')
                    ->label('Product Type')
                    ->options([
                        'unit' => 'Unit',
                        'bundle' => 'Bundle',
                        'service' => 'Service',
                    ])
                    ->default('unit')
                    ->required(),
            ]),

            Grid::make(4)->schema([
                TextInput::make('sku')
                    ->label('SKU')
                    ->helperText('If you sync with WooCommerce, use the same SKU.')
                    ->maxLength(120)
                    ->required(),

                TextInput::make('price')
                    ->label('Selling Price')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->prefix('RM'),

                Grid::make(2)->schema([
                    TextInput::make('weight')
                        ->label('Weight')
                        ->numeric()
                        ->default(0),

                    Select::make('weight_unit')
                        ->label('Unit')
                        ->options([
                            'g' => 'gram',
                            'kg' => 'kg',
                            'ml' => 'ml',
                        ])
                        ->default('g'),
                ]),

                TextInput::make('max_units_per_purchase')
                    ->label('Maximum Unit')
                    ->numeric()
                    ->placeholder('Maximum units per purchase')
                    ->default(0),
            ]),

            Textarea::make('description')
                ->label('Description')
                ->rows(6)
                ->columnSpanFull(),

            FileUpload::make('primary_image_path')
                ->label('Primary Image')
                ->image()
                ->directory('products')
                ->imageEditor(),
        ]);
    }
}