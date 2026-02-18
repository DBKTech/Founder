<?php

namespace App\Filament\App\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('board_row')
                    ->label(view('filament.orders.board-header')) // ✅ header aligns with checkbox
                    ->view('filament.orders.board-row')
                    ->grow()
                    ->extraHeaderAttributes(['class' => '!p-0 !align-top'])
                    ->extraCellAttributes(['class' => '!p-0 w-full !align-top'])
                    ->extraAttributes(['class' => 'w-full']),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
