<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceOrderService
{
    public function create(array $payload): Order
    {
        return DB::transaction(function () use ($payload) {
            $tenantId = $payload['tenant_id'];
            $placedBy = $payload['placed_by_user_id'];
            $tier = $payload['price_tier'] ?? 'SP';

            // 1) Upsert customer by phone (tenant scoped)
            $customer = Customer::query()
                ->where('tenant_id', $tenantId)
                ->where('phone', $payload['customer']['phone'])
                ->first();

            if (! $customer) {
                $customer = Customer::create([
                    'tenant_id' => $tenantId,
                    'name' => $payload['customer']['name'],
                    'phone' => $payload['customer']['phone'],
                ]);
            } else {
                // keep latest name
                $customer->update([
                    'name' => $payload['customer']['name'],
                ]);
            }

            // 2) Status based on payment method
            $paymentMethod = $payload['payment_method'] ?? 'bank_transfer';
            $status = match ($paymentMethod) {
                'cod' => OrderStatus::Pending->value,
                default => OrderStatus::Draft->value, // or PendingPayment if you have it
            };

            // 3) Create order
            $order = Order::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'placed_by_user_id' => $placedBy,

                'order_no' => $this->generateOrderNo($tenantId),
                'status' => $status,
                'source' => 'marketplace',
                'currency' => $payload['currency'] ?? 'MYR',

                'subtotal' => 0,
                'discount_total' => 0,
                'shipping_total' => 0,
                'tax_total' => 0,
                'total' => 0,

                'ordered_at' => now(),
            ]);

            // 4) Create items
            $subtotal = 0;

            foreach ($payload['items'] as $row) {
                $product = Product::query()
                    ->where('tenant_id', $tenantId)
                    ->findOrFail($row['product_id']);

                $qty = max(1, (int) $row['qty']);

                $unit = $this->getUnitPrice($product, $tier);
                $line = round($unit * $qty, 2);

                OrderItem::create([
                    'tenant_id' => $tenantId,
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'title' => $product->name,
                    'sku' => $product->sku,
                    'qty' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $line,
                ]);

                $subtotal += $line;
            }

            // 5) Totals
            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal - $order->discount_total + $order->shipping_total + $order->tax_total,
            ]);

            return $order;
        });
    }

    protected function getUnitPrice(Product $product, string $tier): float
    {
        // If you added price_ap/price_sp columns use them, else fallback to price.
        if ($tier === 'AP' && isset($product->price_ap)) {
            return (float) $product->price_ap;
        }

        if ($tier === 'SP' && isset($product->price_sp)) {
            return (float) $product->price_sp;
        }

        return (float) $product->price;
    }

    protected function generateOrderNo(int $tenantId): string
    {
        // Simple unique order no. Replace with your own format if needed.
        return 'ORD-' . now()->format('ymd') . '-' . Str::upper(Str::random(6));
    }
}