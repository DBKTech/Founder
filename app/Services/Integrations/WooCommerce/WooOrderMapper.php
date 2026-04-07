<?php

namespace App\Services\Integrations\WooCommerce;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Integration;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
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

            $subtotal = $this->calculateOrderSubtotal($payload);
            $discountTotal = (float) data_get($payload, 'discount_total', 0);
            $shippingTotal = (float) data_get($payload, 'shipping_total', 0);
            $taxTotal = (float) data_get($payload, 'total_tax', 0);
            $grandTotal = (float) data_get($payload, 'total', 0);

            $order = Order::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'source' => 'woocommerce',
                    'external_id' => $externalId,
                ],
                [
                    'customer_id' => $customer?->id,
                    'order_no' => (string) (data_get($payload, 'number') ?: $externalId),
                    'status' => $this->mapStatus((string) data_get($payload, 'status')),
                    'currency' => data_get($payload, 'currency', 'MYR'),
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'shipping_total' => $shippingTotal,
                    'tax_total' => $taxTotal,
                    'total' => $grandTotal,
                    'ordered_at' => $this->parseDate(data_get($payload, 'date_created')),
                    'payment_method' => data_get($payload, 'payment_method'),
                    'payment_status' => $this->mapPaymentStatus($payload),
                    'payment_gateway' => 'woocommerce',
                    'payment_ref' => data_get($payload, 'transaction_id'),
                    'paid_at' => $this->parseDate(data_get($payload, 'date_paid')),
                    'meta' => [
                        'woo_order_id' => data_get($payload, 'id'),
                        'woo_order_key' => data_get($payload, 'order_key'),
                        'woo_number' => data_get($payload, 'number'),
                        'payment_method' => data_get($payload, 'payment_method'),
                        'payment_method_title' => data_get($payload, 'payment_method_title'),
                        'billing' => data_get($payload, 'billing'),
                        'shipping' => data_get($payload, 'shipping'),
                        'customer_note' => data_get($payload, 'customer_note'),
                        'date_created' => data_get($payload, 'date_created'),
                        'date_modified' => data_get($payload, 'date_modified'),
                        'date_paid' => data_get($payload, 'date_paid'),
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

    protected function calculateOrderSubtotal(array $payload): float
    {
        $lineItems = (array) data_get($payload, 'line_items', []);

        if (empty($lineItems)) {
            return (float) data_get($payload, 'subtotal', 0);
        }

        return round(collect($lineItems)->sum(function ($item) {
            return (float) data_get($item, 'subtotal', 0);
        }), 2);
    }

    protected function calculateUnitPrice(array $item): float
    {
        $qty = max((int) data_get($item, 'quantity', 1), 1);
        $total = (float) data_get($item, 'total', 0);

        return round($total / $qty, 2);
    }

    protected function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->toDateTimeString();
    }

    protected function mapStatus(string $wooStatus): string
    {
        return match ($wooStatus) {
            'pending' => OrderStatus::Pending->value,
            'on-hold' => OrderStatus::Pending->value,
            'processing' => OrderStatus::Approved->value,
            'completed' => OrderStatus::Completed->value,
            'cancelled' => OrderStatus::Cancelled->value,
            'refunded' => OrderStatus::Returned->value,
            'failed' => OrderStatus::Rejected->value,
            default => OrderStatus::Draft->value,
        };
    }

    protected function mapPaymentStatus(array $payload): string
    {
        if (data_get($payload, 'date_paid')) {
            return 'paid';
        }

        $wooStatus = (string) data_get($payload, 'status');

        return match ($wooStatus) {
            'pending', 'on-hold', 'failed' => 'unpaid',
            'processing', 'completed' => 'paid',
            'cancelled', 'refunded' => 'unpaid',
            default => 'unpaid',
        };
    }

    protected function resolveCustomer(Integration $integration, array $payload): ?Customer
    {
        $tenantId = $integration->tenant_id;

        $email = trim((string) data_get($payload, 'billing.email', ''));
        $firstName = trim((string) data_get($payload, 'billing.first_name', ''));
        $lastName = trim((string) data_get($payload, 'billing.last_name', ''));
        $company = trim((string) data_get($payload, 'billing.company', ''));
        $phone = trim((string) data_get($payload, 'billing.phone', ''));

        if ($email === '' && $phone === '') {
            return null;
        }

        $customer = Customer::query()->firstOrNew([
            'tenant_id' => $tenantId,
            'email' => $email !== '' ? $email : Str::uuid()->toString() . '@placeholder.local',
        ]);

        $customer->name = trim($firstName . ' ' . $lastName)
            ?: ($company !== '' ? $company : 'Woo Customer');

        $customer->phone = $phone !== '' ? $phone : $customer->phone;
        $customer->save();

        return $customer;
    }
}