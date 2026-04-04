<?php

namespace App\Filament\App\Pages\Orders;

class ApprovedOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'Approved';
    protected static ?int $navigationSort = 3;

    protected function getTabKey(): string
    {
        return 'approved';
    }
}