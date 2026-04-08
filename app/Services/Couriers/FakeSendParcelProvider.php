<?php

namespace App\Services\Couriers;

use App\Contracts\CourierProviderInterface;
use App\Models\Order;
use App\Models\Shipment;

class FakeSendParcelProvider implements CourierProviderInterface
{
    public function createShipment(Order $order): array
    {
        $trackingNumber = 'SPP-TEST-' . now()->format('YmdHis') . '-' . $order->id;

        return [
            'success' => true,
            'tracking_number' => $trackingNumber,
            'label_url' => route('shipments.fake-label', ['tracking' => $trackingNumber]),
            'courier_code' => 'sendparcelpro',
            'service_code' => 'standard',
            'status' => 'created',
            'raw' => [
                'message' => 'Fake shipment created successfully.',
                'order_id' => $order->id,
                'tracking_number' => $trackingNumber,
            ],
        ];
    }

    public function cancelShipment(Shipment $shipment): array
    {
        return [
            'success' => true,
            'status' => 'cancelled',
            'raw' => [
                'message' => 'Fake shipment cancelled.',
                'tracking_number' => $shipment->tracking_number,
            ],
        ];
    }

    public function trackShipment(Shipment $shipment): array
    {
        $createdAt = $shipment->created_at ?: now()->subHours(2);

        return [
            'success' => true,
            'status' => 'in_transit',
            'events' => [
                [
                    'status' => 'created',
                    'description' => 'Shipment created',
                    'occurred_at' => $createdAt->copy()->addMinutes(1),
                ],
                [
                    'status' => 'picked_up',
                    'description' => 'Parcel picked up by courier',
                    'occurred_at' => $createdAt->copy()->addMinutes(20),
                ],
                [
                    'status' => 'in_transit',
                    'description' => 'Parcel is in transit',
                    'occurred_at' => $createdAt->copy()->addHour(),
                ],
            ],
            'raw' => [
                'message' => 'Fake tracking response.',
                'tracking_number' => $shipment->tracking_number,
            ],
        ];
    }

    public function getLabel(Shipment $shipment): ?string
    {
        if (blank($shipment->tracking_number)) {
            return null;
        }

        return route('shipments.fake-label', [
            'tracking' => $shipment->tracking_number,
        ]);
    }
}