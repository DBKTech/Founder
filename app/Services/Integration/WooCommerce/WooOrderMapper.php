<?php

namespace App\Services\Integrations\WooCommerce;

use App\Models\Customer;
use App\Models\Integration;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WooOrderMapper
{
    public function import(Integration $integration, array $payload): Order
    {
        return DB::transaction(function () use ($integration, $payload) {
            $tenantId = $integration->tenant_id;
            $externalId = (string) data_get($payload, 'id');

            $customer = $this->resolveCustomer($integration, $payload);

            $order = Order::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'source' => 'woocommerce',
                    'external_id' => $externalId,
                ],
                [
                    'customer_id' => $customer?->id,
                    'status' => $this->mapStatus((string) data_get($payload, 'status')),
                    'currency' => data_get($payload, 'currency', 'MYR'),
                    'subtotal' => (float) data_get($payload, 'subtotal', 0),
                    'discount_total' => (float) data_get($payload, 'discount_total', 0),
                    'shipping_total' => (float) data_get($payload, 'shipping_total', 0),
                    'tax_total' => (float) data_get($payload, 'total_tax', 0),
                    'meta' => [
                        'woo_order_key' => data_get($payload, 'order_key'),
                        'woo_number' => data_get($payload, 'number'),
                        'payment_method' => data_get($payload, 'payment_method'),
                        'payment_method_title' => data_get($payload, 'payment_method_title'),
                        'billing' => data_get($payload, 'billing'),
                        'shipping' => data_get($payload, 'shipping'),
                        'date_created' => data_get($payload, 'date_created'),
                        'date_modified' => data_get($payload, 'date_modified'),
                        'raw_status' => data_get($payload, 'status'),
                    ],
                ]
            );

            OrderItem::query()
                ->where('tenant_id', $tenantId)
                ->where('order_id', $order->id)
                ->delete();

            foreach ((array) data_get($payload, 'line_items', []) as $item) {
                OrderItem::create([
                    'tenant_id' => $tenantId,
                    'order_id' => $order->id,
                    'product_id' => null,
                    'title' => data_get($item, 'name'),
                    'sku' => data_get($item, 'sku'),
                    'qty' => (int) data_get($item, 'quantity', 1),
                    'unit_price' => $this->calculateUnitPrice($item),
                    'line_total' => (float) data_get($item, 'total', 0),
                ]);
            }

            return $order->fresh();
        });
    }

    protected function calculateUnitPrice(array $item): float
    {
        $qty = max((int) data_get($item, 'quantity', 1), 1);
        $total = (float) data_get($item, 'total', 0);

        return round($total / $qty, 2);
    }

    protected function mapStatus(string $wooStatus): string
    {
        return match ($wooStatus) {
            'pending' => 'pending',
            'on-hold' => 'pending',
            'processing' => 'approved',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'returned',
            'failed' => 'rejected',
            default => 'draft',
        };
    }

    protected function resolveCustomer(Integration $integration, array $payload): ?Customer
    {
        if (! class_exists(Customer::class)) {
            return null;
        }

        $tenantId = $integration->tenant_id;
        $email = data_get($payload, 'billing.email');
        $firstName = data_get($payload, 'billing.first_name');
        $lastName = data_get($payload, 'billing.last_name');
        $phone = data_get($payload, 'billing.phone');

        if (! $email && ! $phone) {
            return null;
        }

        $customer = Customer::query()->firstOrNew([
            'tenant_id' => $tenantId,
            'email' => $email ?: Str::uuid()->toString() . '@placeholder.local',
        ]);

        $customer->name = trim($firstName . ' ' . $lastName) ?: 'Woo Customer';
        $customer->phone = $phone;
        $customer->save();

        return $customer;
    }
}