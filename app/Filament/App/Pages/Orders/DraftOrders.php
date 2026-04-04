<?php

namespace App\Filament\App\Pages\Orders;

class DraftOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'Draft';
    protected static ?int $navigationSort = 9;

    protected function getTabKey(): string
    {
        return 'draft';
    }
}