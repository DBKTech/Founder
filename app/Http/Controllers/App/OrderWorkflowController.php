<?php

namespace App\Http\Controllers\App;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderWorkflowController extends Controller
{
    public function handle(Request $request, Order $order, string $action)
    {
        DB::transaction(function () use ($order, $action) {

            match ($action) {
                // board-row keys -> existing methods
                'approval' => $this->markPaid($order),
                'start-processing' => $this->startProcessing($order),

                // FounderHQ board-row typically uses "push-courier"
                // We map it to your "markShipped" (Processing -> Shipped + shipment stub)
                'push-courier' => $this->markShipped($order),

                // board-row "reject" -> cancel
                'reject' => $this->cancel($order),

                // shipment lifecycle buttons
                'mark-delivered' => $this->markDelivered($order),
                'mark-returned' => $this->markReturned($order),

                // AWB actions
                'cancel-awb' => $this->cancelAwb($order),
                'reprint-awb' => $this->reprintAwb($order),
                'delivery-order' => $this->deliveryOrder($order),

                // integrations
                'sync-woo' => $this->syncWoo($order),

                default => abort(404),
            };
        });

        Notification::make()
            ->title('Order updated')
            ->success()
            ->send();

        return back();
    }

    protected function markPaid(Order $order): void
    {
        if (! in_array($order->status, [OrderStatus::Draft, OrderStatus::PendingPayment], true)) {
            abort(403, 'Invalid transition');
        }

        $order->update([
            'status' => OrderStatus::Paid,
        ]);
    }

    protected function startProcessing(Order $order): void
    {
        if ($order->status !== OrderStatus::Paid) {
            abort(403);
        }

        $order->update([
            'status' => OrderStatus::Processing,
        ]);
    }

    protected function markShipped(Order $order): void
    {
        if ($order->status !== OrderStatus::Processing) {
            abort(403);
        }

        $shipment = $order->shipment ?? Shipment::create([
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'status' => OrderStatus::Shipped->value,
            'shipped_at' => now(),
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,     // ✅ important for tenancy
            'shipment_id' => $shipment->id,
            'status' => OrderStatus::Shipped->value,
        ]);

        $order->update([
            'status' => OrderStatus::Shipped,
        ]);
    }

    protected function markDelivered(Order $order): void
    {
        if ($order->status !== OrderStatus::Shipped) {
            abort(403);
        }

        $shipment = $order->shipment;

        if (! $shipment) {
            abort(403, 'Shipment missing');
        }

        $shipment->update([
            'status' => OrderStatus::Delivered->value,
            'delivered_at' => now(),
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,     // ✅ important
            'shipment_id' => $shipment->id,
            'status' => OrderStatus::Delivered->value,
        ]);

        $order->update([
            'status' => OrderStatus::Delivered,
        ]);
    }

    protected function cancel(Order $order): void
    {
        if (in_array($order->status, [OrderStatus::Delivered, OrderStatus::Refunded], true)) {
            abort(403);
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
        ]);
    }

    protected function refund(Order $order): void
    {
        if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Delivered], true)) {
            abort(403);
        }

        $order->update([
            'status' => OrderStatus::Refunded,
        ]);
    }

    // -----------------------------
    // Extra actions used by board-row
    // -----------------------------

    protected function markReturned(Order $order): void
    {
        $shipment = $order->shipment;

        if (! $shipment) {
            abort(403, 'Shipment missing');
        }

        $shipment->update([
            'status' => 'returned',
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,
            'shipment_id' => $shipment->id,
            'status' => 'returned',
        ]);

        // You don't have OrderStatus::Returned, so using Refunded as placeholder
        $order->update([
            'status' => OrderStatus::Refunded,
        ]);
    }

    protected function cancelAwb(Order $order): void
    {
        $shipment = $order->shipment;

        if (! $shipment || empty($shipment->tracking_number)) {
            abort(403, 'No AWB / tracking number');
        }

        $shipment->update([
            'status' => 'cancelled',
            'tracking_number' => null,
            // if you have label_url column, you can null it too
            // 'label_url' => null,
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,
            'shipment_id' => $shipment->id,
            'status' => 'cancelled',
        ]);
    }

    protected function reprintAwb(Order $order): void
    {
        // Placeholder: implement when you have label_url / awb pdf url
        // Example:
        // $url = $order->shipment?->label_url;
        // if ($url) { redirect()->away($url)->send(); return; }

        Notification::make()
            ->title('Re-print AWB not implemented yet')
            ->warning()
            ->send();
    }

    protected function deliveryOrder(Order $order): void
    {
        // Placeholder: implement PDF generate later
        Notification::make()
            ->title('Delivery Order not implemented yet')
            ->warning()
            ->send();
    }

    protected function syncWoo(Order $order): void
    {
        // placeholder – nanti buat Job
        $order->update(['last_synced_at' => now()]);
    }
}