<?php

namespace App\Filament\Platform\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->getStateUsing(fn($record, $rowLoop) => $rowLoop->iteration),

                ImageColumn::make('primary_image_path')
                    ->label('Image')
                    ->square()
                    ->size(44),

                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                // later boleh tambah filter by brand/status
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}