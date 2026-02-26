<?php

namespace App\Filament\Platform\Resources\Brands\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            /**
             * tenant_id:
             * - DO NOT show any tenant field
             * - Your model trait (BelongsToTenant) will set it on create
             * - Pages mutateFormDataBeforeCreate/Save also force it
             */
            Hidden::make('tenant_id')
                ->dehydrated(false),

            // Auto slug but hidden
            Hidden::make('slug')
                ->dehydrated()
                ->required(),

            TextInput::make('name')
                ->label('Brand Name')
                ->required()
                ->maxLength(120)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('slug', Str::slug((string) $state));
                }),

            CheckboxList::make('visible_to_user_types')
                ->label('Visible To')
                ->options([
                    'tenant_user' => 'Seller',
                    'stokis' => 'Stokis',
                ])
                ->columns(2)
                ->required(),
        ]);
    }
}