<?php

namespace App\Filament\App\Pages;

use App\Models\Order;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class OrderSummary extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static UnitEnum|string|null $navigationGroup = null;
    protected static ?string $navigationLabel = null;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'order-summary/{order}';

    public int $orderId;
    public string $copyType = 'buyer'; // buyer | seller

    public function mount(Order $order): void
    {
        $this->orderId = (int) $order->getKey();
    }

    public function getOrderProperty(): Order
    {
        return Order::query()
            ->with(['tenant', 'customer', 'shipment', 'items.product'])
            ->findOrFail($this->orderId);
    }

    public function setCopyType(string $type): void
    {
        if (in_array($type, ['buyer', 'seller'], true)) {
            $this->copyType = $type;
        }
    }

    protected function resolveOrderTotal(Order $order): float
    {
        $possibleTotals = [
            $order->total ?? null,
            $order->total_amount ?? null,
            $order->grand_total ?? null,
        ];

        foreach ($possibleTotals as $value) {
            if ($value !== null) {
                return (float) $value;
            }
        }

        $subtotal = (float) ($order->subtotal ?? 0);
        $shipping = (float) ($order->shipping_total ?? $order->shipping_fee ?? 0);
        $tax = (float) ($order->tax_total ?? $order->tax ?? 0);
        $discount = (float) ($order->discount_total ?? 0);

        return max(0, $subtotal + $shipping + $tax - $discount);
    }

    public function getView(): string
    {
        return 'filament.app.pages.order-summary';
    }
}