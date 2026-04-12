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

        $currentStatus = $shipment->status;

        $nextStatus = match ($currentStatus) {
            'created' => 'picked_up',
            'awb_printed' => 'picked_up',
            'picked_up' => 'in_transit',
            'in_transit' => 'out_for_delivery',
            'out_for_delivery' => 'delivered',
            default => $currentStatus,
        };

        $events = [
            [
                'status' => 'created',
                'description' => 'Shipment created',
                'occurred_at' => $createdAt->copy()->addMinutes(1),
            ],
        ];

        if (in_array($nextStatus, ['picked_up', 'in_transit', 'out_for_delivery', 'delivered'], true)) {
            $events[] = [
                'status' => 'picked_up',
                'description' => 'Parcel picked up by courier',
                'occurred_at' => $createdAt->copy()->addMinutes(20),
            ];
        }

        if (in_array($nextStatus, ['in_transit', 'out_for_delivery', 'delivered'], true)) {
            $events[] = [
                'status' => 'in_transit',
                'description' => 'Parcel is in transit',
                'occurred_at' => $createdAt->copy()->addHour(),
            ];
        }

        if (in_array($nextStatus, ['out_for_delivery', 'delivered'], true)) {
            $events[] = [
                'status' => 'out_for_delivery',
                'description' => 'Parcel is out for delivery',
                'occurred_at' => $createdAt->copy()->addHours(2),
            ];
        }

        if ($nextStatus === 'delivered') {
            $events[] = [
                'status' => 'delivered',
                'description' => 'Parcel delivered successfully',
                'occurred_at' => $createdAt->copy()->addHours(3),
            ];
        }

        return [
            'success' => true,
            'status' => $nextStatus,
            'events' => $events,
            'raw' => [
                'message' => 'Fake tracking response.',
                'tracking_number' => $shipment->tracking_number,
                'previous_status' => $currentStatus,
                'current_status' => $nextStatus,
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