<?php

namespace App\Filament\Platform\Resources\Brands\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->getStateUsing(function ($record, $rowLoop) {
                        return $rowLoop->iteration;
                    }),
                    
                TextColumn::make('name')
                    ->label('Brand Name')
                    ->searchable()
                    ->sortable(),

                // ✅ Virtual column (tak bergantung pada state Filament)
                TextColumn::make('for_role')
                    ->label('For Role')
                    ->getStateUsing(function ($record) {
                        $map = [
                            'tenant_user' => 'Seller',
                            'stokis' => 'Stokis',
                        ];

                        // Ambil raw value terus dari DB (confirm ada ["tenant_user"])
                        $raw = $record->getRawOriginal('visible_to_user_types');

                        $values = is_array($raw)
                            ? $raw
                            : (array) json_decode((string) $raw, true);

                        $values = array_values(array_filter($values));

                        return count($values)
                            ? collect($values)->map(fn($v) => $map[$v] ?? $v)->implode(', ')
                            : '-';
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}