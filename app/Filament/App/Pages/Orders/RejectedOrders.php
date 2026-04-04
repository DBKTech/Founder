<?php

namespace App\Filament\App\Pages\Orders;

class RejectedOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'Rejected';
    protected static ?int $navigationSort = 6;

    protected function getTabKey(): string
    {
        return 'rejected';
    }
}