<?php

namespace App\Support\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;

class OrderRowActions
{
    public static function for(Order $order): array
    {
        $shipment = $order->shipment;

        $hasShipment = (bool) $shipment;
        $hasTracking = $shipment && !empty($shipment->tracking_number);

        $shipmentStatus = strtolower((string) ($shipment?->status ?? ''));
        $courier = $shipment?->courier_name
            ?? $shipment?->courier_title
            ?? $shipment?->courier_code
            ?? null;

        $actions = [];

        $add = function (array $a) use (&$actions) {
            $actions[] = array_merge([
                'style' => 'primary',  // primary|success|danger|muted|dark|... (your UI mapper)
                'confirm' => null,
                'href' => null,        // if you want link buttons later
            ], $a);
        };

        // Normalize status value (supports enum casting + legacy string)
        $statusValue = $order->status instanceof OrderStatus
            ? $order->status->value
            : strtolower((string) $order->status);

        // ---- ACTIONS BY ORDER STATUS (your new spec) ----

        // COMPLETED
        if ($statusValue === OrderStatus::Completed->value) {
            // Tracking Number display is UI, not button
            if ($hasTracking) {
                $add(['key' => 'cancel-awb', 'label' => 'Cancel AWB', 'style' => 'danger-outline', 'confirm' => 'Cancel this AWB?']);
                $add(['key' => 'mark-delivered', 'label' => 'Mark As Delivered', 'style' => 'success-outline', 'confirm' => 'Mark as delivered?']);
                $add(['key' => 'reprint-awb', 'label' => 'Re-Print AWB', 'style' => 'muted']);
            }
            $add(['key' => 'order-details', 'label' => 'Order Details', 'style' => 'dark']);
            return $actions;
        }

        // APPROVED
        if ($statusValue === OrderStatus::Approved->value) {
            $label = $courier ? "Push To {$courier}" : "Push To Pos Malaysia";
            $add(['key' => 'push-courier', 'label' => $label, 'style' => 'success']);
            $add(['key' => 'reject', 'label' => 'Reject Order', 'style' => 'danger', 'confirm' => 'Reject this order?']);
            return $actions;
        }

        // PENDING (COD)
        if ($statusValue === OrderStatus::Pending->value) {
            if ($hasTracking) {
                $add(['key' => 'cancel-awb', 'label' => 'Cancel AWB', 'style' => 'danger-outline', 'confirm' => 'Cancel this AWB?']);
                $add(['key' => 'mark-delivered', 'label' => 'Mark As Delivered', 'style' => 'success-outline', 'confirm' => 'Mark as delivered?']);
                $add(['key' => 'mark-returned', 'label' => 'Mark As Returned', 'style' => 'warning-outline', 'confirm' => 'Mark as returned?']);
                $add(['key' => 'reprint-awb', 'label' => 'Re-Print AWB', 'style' => 'muted']);
            }
            $add(['key' => 'order-details', 'label' => 'Order Details', 'style' => 'dark']);
            return $actions;
        }

        // ON THE MOVE (online) - same as completed
        if ($statusValue === OrderStatus::OnTheMove->value) {
            if ($hasTracking) {
                $add(['key' => 'cancel-awb', 'label' => 'Cancel AWB', 'style' => 'danger-outline', 'confirm' => 'Cancel this AWB?']);
                $add(['key' => 'mark-delivered', 'label' => 'Mark As Delivered', 'style' => 'success-outline', 'confirm' => 'Mark as delivered?']);
                $add(['key' => 'reprint-awb', 'label' => 'Re-Print AWB', 'style' => 'muted']);
            }
            $add(['key' => 'order-details', 'label' => 'Order Details', 'style' => 'dark']);
            return $actions;
        }

        // UNPRINT AWB
        if ($statusValue === OrderStatus::UnprintAwb->value) {
            if ($hasTracking) {
                // remove cancel-awb per your spec
                $add(['key' => 'mark-delivered', 'label' => 'Mark As Delivered', 'style' => 'success-outline', 'confirm' => 'Mark as delivered?']);
                $add(['key' => 'print-awb', 'label' => 'Print AWB', 'style' => 'muted']);
            } else {
                // fallback: if no tracking yet, allow push
                $label = $courier ? "Push To {$courier}" : "Push To Pos Malaysia";
                $add(['key' => 'push-courier', 'label' => $label, 'style' => 'success']);
            }
            $add(['key' => 'order-details', 'label' => 'Order Details', 'style' => 'dark']);
            return $actions;
        }

        // RETURNED (tracking display only; no actions)
        if ($statusValue === OrderStatus::Returned->value) {
            return $actions;
        }

        // REJECTED / CANCELLED (nothing)
        if (in_array($statusValue, [OrderStatus::Rejected->value, OrderStatus::Cancelled->value], true)) {
            return $actions;
        }

        // DRAFT (not sure yet) - safest: allow details/edit
        if ($statusValue === OrderStatus::Draft->value) {
            $add(['key' => 'order-details', 'label' => 'Order Details', 'style' => 'dark']);
            return $actions;
        }

        return $actions;
    }
}