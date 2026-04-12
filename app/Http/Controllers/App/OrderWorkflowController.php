<?php

namespace App\Http\Controllers\App;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShipmentEvent;
use App\Services\Couriers\CourierManager;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderWorkflowController extends Controller
{
    public function handle(
        Request $request,
        Order $order,
        string $action,
        CourierManager $courierManager
    ): RedirectResponse {
        abort_unless($request->user()?->tenant_id === $order->tenant_id, 403);

        $response = DB::transaction(function () use ($order, $action, $courierManager) {
            return match ($action) {
                'approval' => $this->approve($order),
                'push-courier' => $this->pushCourier($order, $courierManager),
                'print-awb' => $this->printAwb($order),
                'reprint-awb' => $this->reprintAwb($order),
                'cancel-awb' => $this->cancelAwb($order, $courierManager),

                'mark-delivered' => $this->markDelivered($order),
                'mark-returned' => $this->markReturned($order),

                'reject' => $this->reject($order),
                'cancel' => $this->cancel($order),
                'delivery-order' => $this->deliveryOrder($order),

                'sync-woo' => $this->syncWoo($order),

                default => abort(404),
            };
        });

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        Notification::make()
            ->title('Order updated')
            ->success()
            ->send();

        return back();
    }

    protected function approve(Order $order): void
    {
        if ($order->status !== OrderStatus::Draft) {
            abort(403, 'Invalid transition');
        }

        $order->update([
            'status' => OrderStatus::Approved,
        ]);
    }

    protected function pushCourier(Order $order, CourierManager $courierManager): RedirectResponse
    {
        if ($order->status !== OrderStatus::Approved) {
            abort(403, 'Invalid transition');
        }

        $provider = $courierManager->provider('sendparcelpro');

        $result = $provider->createShipment($order);

        if (!($result['success'] ?? false)) {
            return back()->with('danger', 'Failed to create shipment.');
        }

        $shipment = $order->shipment()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'tenant_id' => $order->tenant_id,
                'courier_code' => $result['courier_code'] ?? 'sendparcelpro',
                'service_code' => $result['service_code'] ?? 'standard',
                'tracking_number' => $result['tracking_number'] ?? null,
                'label_url' => $result['label_url'] ?? null,
                'status' => $result['status'] ?? 'created',
                'shipped_at' => now(),
                'meta' => $result['raw'] ?? [],
            ]
        );

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,
            'shipment_id' => $shipment->id,
            'status' => 'created',
        ]);

        $order->update([
            'status' => OrderStatus::UnprintAwb,
        ]);

        return back()->with('success', "Shipment created. Tracking: {$shipment->tracking_number}");
    }

    protected function printAwb(Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::UnprintAwb) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment;

        if (!$shipment || empty($shipment->tracking_number)) {
            abort(403, 'No AWB / tracking number');
        }

        if (schema_has_column('orders', 'awb_printed_at')) {
            $order->awb_printed_at = now();
        }

        $shipment->update([
            'status' => 'awb_printed',
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,
            'shipment_id' => $shipment->id,
            'status' => 'awb_printed',
        ]);

        $order->status = $this->isCod($order)
            ? OrderStatus::Pending
            : OrderStatus::OnTheMove;

        $order->save();

        if (!empty($shipment->label_url)) {
            return redirect()->away($shipment->label_url);
        }

        return back()->with('warning', 'AWB printed, but no label URL found.');
    }

    protected function markDelivered(Order $order): void
    {
        if (
            !in_array($order->status, [
                OrderStatus::Pending,
                OrderStatus::OnTheMove,
                OrderStatus::Completed,
            ], true)
        ) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment;

        if (!$shipment) {
            abort(403, 'Shipment missing');
        }

        $shipment->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,
            'shipment_id' => $shipment->id,
            'status' => 'delivered',
        ]);

        $order->update([
            'status' => OrderStatus::Completed,
        ]);
    }

    protected function markReturned(Order $order): void
    {
        if (
            !in_array($order->status, [
                OrderStatus::Pending,
                OrderStatus::OnTheMove,
                OrderStatus::Completed,
            ], true)
        ) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment;

        if (!$shipment) {
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

        $order->update([
            'status' => OrderStatus::Returned,
        ]);
    }

    protected function reject(Order $order): void
    {
        if (
            !in_array($order->status, [
                OrderStatus::Draft,
                OrderStatus::Approved,
                OrderStatus::UnprintAwb,
            ], true)
        ) {
            abort(403, 'Invalid transition');
        }

        $order->update([
            'status' => OrderStatus::Rejected,
        ]);
    }

    protected function cancel(Order $order): void
    {
        if (
            in_array($order->status, [
                OrderStatus::Completed,
                OrderStatus::Returned,
            ], true)
        ) {
            abort(403, 'Invalid transition');
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
        ]);
    }

    protected function cancelAwb(Order $order, CourierManager $courierManager): void
    {
        if ($order->status === OrderStatus::UnprintAwb) {
            abort(403, 'Cancel AWB not allowed for Unprint AWB status');
        }

        if (
            !in_array($order->status, [
                OrderStatus::Pending,
                OrderStatus::OnTheMove,
                OrderStatus::Completed,
            ], true)
        ) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment;

        if (!$shipment) {
            abort(403, 'Shipment missing');
        }

        if (empty($shipment->tracking_number)) {
            abort(403, 'No AWB / tracking number');
        }

        $provider = $courierManager->provider($shipment->courier_code ?? 'sendparcelpro');

        $result = $provider->cancelShipment($shipment);

        if (!($result['success'] ?? false)) {
            abort(500, 'Failed to cancel shipment');
        }

        $shipment->update([
            'status' => $result['status'] ?? 'cancelled',
            'tracking_number' => null,
            'meta' => array_merge($shipment->meta ?? [], [
                'cancel_response' => $result['raw'] ?? [],
            ]),
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,
            'shipment_id' => $shipment->id,
            'status' => 'cancelled',
        ]);

        $order->update([
            'status' => OrderStatus::Approved,
        ]);
    }

    protected function reprintAwb(Order $order): RedirectResponse
    {
        if (
            !in_array($order->status, [
                OrderStatus::Pending,
                OrderStatus::OnTheMove,
                OrderStatus::Completed,
            ], true)
        ) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment;

        if (!$shipment || empty($shipment->tracking_number)) {
            return back()->with('danger', 'No AWB / tracking number.');
        }

        if (empty($shipment->label_url)) {
            return back()->with('danger', 'No AWB label available.');
        }

        return redirect()->away($shipment->label_url);
    }

    protected function deliveryOrder(Order $order): void
    {
        Notification::make()
            ->title('Delivery Order not implemented yet')
            ->warning()
            ->send();
    }

    protected function syncWoo(Order $order): void
    {
        $order->update([
            'last_synced_at' => now(),
        ]);
    }

    protected function isCod(Order $order): bool
    {
        if (isset($order->is_cod)) {
            return (bool) $order->is_cod;
        }

        if (isset($order->payment_method)) {
            $pm = strtolower((string) $order->payment_method);

            return in_array($pm, ['cod', 'cash_on_delivery', 'cash-on-delivery'], true);
        }

        if (isset($order->payment_type)) {
            $pt = strtolower((string) $order->payment_type);

            return in_array($pt, ['cod', 'cash_on_delivery', 'cash-on-delivery'], true);
        }

        return false;
    }
}

if (!function_exists('schema_has_column')) {
    function schema_has_column(string $table, string $column): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}