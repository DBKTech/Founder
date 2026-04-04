<?php

namespace App\Filament\App\Pages\Orders;

class ReturnedOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'Returned';
    protected static ?int $navigationSort = 8;

    protected function getTabKey(): string
    {
        return 'returned';
    }
}