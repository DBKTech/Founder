<?php

namespace App\Filament\App\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use Filament\Actions\Action;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
                    ->grow()
                    ->extraHeaderAttributes(['class' => '!p-0 !align-top'])
                    ->extraCellAttributes(['class' => '!p-0 w-full !align-top'])
                    ->extraAttributes(['class' => 'w-full']),
            ]);
    }
}
