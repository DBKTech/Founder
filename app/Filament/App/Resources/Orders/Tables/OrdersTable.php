<?php

namespace App\Filament\App\Resources\Orders\Tables;

use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                ViewColumn::make('board_row')
                    ->label(view('filament.orders.board-header'))
                    ->view('filament.orders.board-row')
                    ->extraHeaderAttributes([
                        'class' => 'orders-board-header-cell !p-0 !align-top',
                    ])
                    ->extraCellAttributes([
                        'class' => 'orders-board-row-cell !p-0 !align-top',
                    ]),
            ]);
    }
}