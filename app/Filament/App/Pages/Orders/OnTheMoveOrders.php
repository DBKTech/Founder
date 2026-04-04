<?php

namespace App\Filament\App\Pages\Orders;

class OnTheMoveOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'On The Move';
    protected static ?int $navigationSort = 5;

    protected function getTabKey(): string
    {
        return 'on_the_move';
    }
}