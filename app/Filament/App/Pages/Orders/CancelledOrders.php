<?php

namespace App\Filament\App\Pages\Orders;

class CancelledOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'Cancelled';
    protected static ?int $navigationSort = 7;

    protected function getTabKey(): string
    {
        return 'cancelled';
    }
}