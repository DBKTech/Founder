<?php

namespace App\Filament\App\Pages\Orders;

class PendingOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'Pending';
    protected static ?int $navigationSort = 4;

    protected function getTabKey(): string
    {
        return 'pending';
    }
}