<?php

namespace App\Filament\App\Pages\Orders;

class CompletedOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'Completed';
    protected static ?int $navigationSort = 2;

    protected function getTabKey(): string
    {
        return 'completed';
    }
}