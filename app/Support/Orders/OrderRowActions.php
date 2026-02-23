<?php

namespace App\Support\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;

class OrderRowActions
{
    public static function for(Order $order): array
    {
        // Adjust ikut relationship sebenar kau
        $shipment = $order->shipment; // or $order->shipments()->latest()->first()

        $hasShipment = (bool) $shipment;
        $hasTracking = $shipment && !empty($shipment->tracking_number);
        $shipmentStatus = $shipment?->status; // string e.g. pending_collection, shipped, delivered
        $courier = $shipment?->courier_name;   // string

        $actions = [];

        // --- ORDER workflow (approval / reject) ---
        if (in_array($order->status, [OrderStatus::Draft, OrderStatus::PendingPayment], true)) {
            $actions[] = ['key' => 'approval', 'label' => 'Approval', 'style' => 'primary'];
            $actions[] = ['key' => 'reject', 'label' => 'Reject Order', 'style' => 'danger'];
        }

        // --- PROCESSING -> logistics ---
        if ($order->status === OrderStatus::Processing) {
            if (! $hasShipment) {
                $actions[] = ['key' => 'push-courier', 'label' => 'Push To Courier', 'style' => 'success'];
            } else {
                // Already has shipment, show courier push with label
                $label = $courier ? "Push To {$courier}" : "Push To Courier";
                $actions[] = ['key' => 'push-courier', 'label' => $label, 'style' => 'success'];
            }

            $actions[] = ['key' => 'reject', 'label' => 'Reject Order', 'style' => 'danger'];
        }

        // --- Shipment/AWB actions (like your picture 2) ---
        if ($hasShipment) {
            if ($hasTracking) {
                $actions[] = ['key' => 'reprint-awb', 'label' => 'Re-Print AWB', 'style' => 'muted'];
                $actions[] = ['key' => 'delivery-order', 'label' => 'Delivery Order', 'style' => 'dark'];
            }

            // cancel AWB only if not delivered/returned
            if ($hasTracking && !in_array($shipmentStatus, ['delivered', 'returned'], true)) {
                $actions[] = ['key' => 'cancel-awb', 'label' => 'Cancel AWB', 'style' => 'danger-outline'];
            }

            // mark delivered/returned when shipped / pending collection etc
            if (in_array($shipmentStatus, ['shipped', 'in_transit', 'pending_collection'], true)) {
                $actions[] = ['key' => 'mark-delivered', 'label' => 'Mark As Delivered', 'style' => 'success-outline'];
                $actions[] = ['key' => 'mark-returned', 'label' => 'Mark As Returned', 'style' => 'warning-outline'];
            }
        }

        return $actions;
    }
}
