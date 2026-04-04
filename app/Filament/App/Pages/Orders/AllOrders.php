<?php

namespace App\Filament\App\Pages\Orders;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AllOrders extends BaseOrdersPage
{
    protected static ?string $navigationLabel = 'All';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected function getTabKey(): string
    {
        return 'all';
    }
}