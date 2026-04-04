<?php

namespace App\Filament\App\Pages\Orders;

use App\Filament\App\Resources\Orders\OrderResource;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

abstract class BaseOrdersPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Orders';
    protected static string|BackedEnum|null $navigationIcon = null;

    abstract protected function getTabKey(): string;

    public function mount()
    {
        return redirect()->to(OrderResource::getUrl('index', [
            'tab' => $this->getTabKey(),
        ]));
    }

    public function getView(): string
    {
        return 'filament.app.pages.orders.redirect-placeholder';
    }
}