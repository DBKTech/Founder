<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Services\Couriers\CourierManager;
use Illuminate\Console\Command;

class SyncShipmentsCommand extends Command
{
    protected $signature = 'shipments:sync';

    protected $description = 'Sync shipment statuses from courier providers';

    public function handle(CourierManager $courierManager): int
    {
        Shipment::query()
            ->with('order')
            ->whereNotNull('tracking_number')
            ->whereIn('status', [
                'created',
                'awb_printed',
                'picked_up',
                'in_transit',
                'out_for_delivery',
            ])
            ->chunkById(100, function ($shipments) use ($courierManager) {
                foreach ($shipments as $shipment) {
                    try {
                        $provider = $courierManager->provider($shipment->courier_code ?: 'sendparcelpro');

                        $result = $provider->trackShipment($shipment);

                        if (!($result['success'] ?? false)) {
                            $this->warn("Tracking failed for shipment #{$shipment->id}");
                            continue;
                        }

                        $newStatus = $result['status'] ?? $shipment->status;

                        $shipment->update([
                            'status' => $newStatus,
                            'meta' => array_merge($shipment->meta ?? [], [
                                'tracking_response' => $result['raw'] ?? [],
                                'last_synced_at' => now()->toDateTimeString(),
                            ]),
                        ]);

                        foreach (($result['events'] ?? []) as $event) {
                            ShipmentEvent::firstOrCreate(
                                [
                                    'shipment_id' => $shipment->id,
                                    'status' => $event['status'],
                                    'occurred_at' => $event['occurred_at'],
                                ],
                                [
                                    'tenant_id' => $shipment->tenant_id,
                                    'description' => $event['description'] ?? null,
                                    'meta' => [],
                                ]
                            );
                        }

                        $order = $shipment->order;

                        if ($order) {
                            if ($newStatus === 'delivered') {
                                $shipment->update([
                                    'delivered_at' => $shipment->delivered_at ?: now(),
                                ]);

                                if ($order->status !== OrderStatus::Completed) {
                                    $order->update([
                                        'status' => OrderStatus::Completed,
                                    ]);
                                }
                            } elseif (in_array($newStatus, ['picked_up', 'in_transit', 'out_for_delivery'], true)) {
                                if (!in_array($order->status, [OrderStatus::Completed, OrderStatus::Returned], true)) {
                                    $order->update([
                                        'status' => OrderStatus::OnTheMove,
                                    ]);
                                }
                            } elseif ($newStatus === 'returned') {
                                if ($order->status !== OrderStatus::Returned) {
                                    $order->update([
                                        'status' => OrderStatus::Returned,
                                    ]);
                                }
                            }
                        }

                        $this->info("Synced shipment #{$shipment->id} ({$shipment->tracking_number}) => {$newStatus}");
                    } catch (\Throwable $e) {
                        $this->error("Shipment #{$shipment->id} sync failed: {$e->getMessage()}");
                    }
                }
            });

        $this->info('Shipments synced successfully.');

        return self::SUCCESS;
    }
}