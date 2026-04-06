<?php

namespace App\Services\Integrations\WooCommerce;

use App\Models\Integration;
use Illuminate\Support\Carbon;

class WooSyncService
{
    public function test(Integration $integration): array
    {
        $client = new WooClient($integration);
        $result = $client->testConnection();

        $integration->update([
            'status' => $result['ok'] ? 'connected' : 'failed',
            'last_tested_at' => now(),
        ]);

        return $result;
    }

    public function importRecentOrders(Integration $integration, int $perPage = 20): array
    {
        $client = new WooClient($integration);
        $mapper = new WooOrderMapper();

        $orders = $client->getOrders([
            'per_page' => $perPage,
            'orderby' => 'date',
            'order' => 'desc',
        ]);

        $imported = [];

        foreach ($orders as $payload) {
            $imported[] = $mapper->import($integration, $payload)->id;
        }

        $integration->update([
            'status' => 'connected',
            'last_synced_at' => now(),
        ]);

        return [
            'count' => count($imported),
            'order_ids' => $imported,
        ];
    }

    public function syncUpdatedOrders(Integration $integration): array
    {
        $client = new WooClient($integration);
        $mapper = new WooOrderMapper();

        $after = $integration->last_synced_at
            ? Carbon::parse($integration->last_synced_at)->toIso8601String()
            : now()->subDays(7)->toIso8601String();

        $orders = $client->getOrders([
            'per_page' => 50,
            'orderby' => 'date',
            'order' => 'desc',
            'after' => $after,
        ]);

        $count = 0;

        foreach ($orders as $payload) {
            $mapper->import($integration, $payload);
            $count++;
        }

        $integration->update([
            'last_synced_at' => now(),
            'status' => 'connected',
        ]);

        return ['count' => $count];
    }

    public function syncSingleOrder(Integration $integration, int|string $wooOrderId): array
    {
        $client = new WooClient($integration);
        $mapper = new WooOrderMapper();

        $payload = $client->getOrder($wooOrderId);
        $order = $mapper->import($integration, $payload);

        $integration->update([
            'last_synced_at' => now(),
            'status' => 'connected',
        ]);

        return [
            'local_order_id' => $order->id,
            'external_id' => $wooOrderId,
        ];
    }
}