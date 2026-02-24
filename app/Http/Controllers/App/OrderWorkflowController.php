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
        abort_unless($request->user()?->tenant_id === $order->tenant_id, 403);

        DB::transaction(function () use ($request, $order, $action) {
            match ($action) {
                // board-row keys
                'approval'       => $this->approve($order),
                'push-courier'   => $this->pushCourier($order),
                'print-awb'      => $this->printAwb($order),     // NEW
                'reprint-awb'    => $this->reprintAwb($order),
                'cancel-awb'     => $this->cancelAwb($order),

                'mark-delivered' => $this->markDelivered($order),
                'mark-returned'  => $this->markReturned($order),

                'reject'         => $this->reject($order),
                'cancel'         => $this->cancel($order),      // OPTIONAL
                'delivery-order' => $this->deliveryOrder($order),

                'sync-woo'       => $this->syncWoo($order),

                default => abort(404),
            };
        });

        Notification::make()
            ->title('Order updated')
            ->success()
            ->send();

        return back();
    }

    // -----------------------------
    // Core transitions (new statuses)
    // -----------------------------

    protected function approve(Order $order): void
    {
        // Draft -> Approved
        if ($order->status !== OrderStatus::Draft) {
            abort(403, 'Invalid transition');
        }

        $order->update(['status' => OrderStatus::Approved]);
    }

    /**
     * Approved -> UnprintAwb
     * Creates shipment + tracking if needed (simulate "pushed to courier")
     * NOTE: integrate courier API later.
     */
    protected function pushCourier(Order $order): void
    {
        if (!in_array($order->status, [OrderStatus::Approved, OrderStatus::UnprintAwb], true)) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment ?: Shipment::create([
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'status' => 'created',
        ]);

        // If no tracking, generate placeholder tracking (until real courier integration)
        if (empty($shipment->tracking_number)) {
            $shipment->update([
                'tracking_number' => $shipment->tracking_number ?: ('TMP-' . strtoupper(str()->random(10))),
                'status' => 'awb_created',
                'shipped_at' => now(),
            ]);

            ShipmentEvent::create([
                'tenant_id' => $order->tenant_id,
                'shipment_id' => $shipment->id,
                'status' => 'awb_created',
            ]);
        }

        // After pushing, order becomes UnprintAwb
        $order->update(['status' => OrderStatus::UnprintAwb]);
    }

    /**
     * UnprintAwb -> Pending (COD) OR OnTheMove (online)
     * This represents "AWB printed / ready, picked up / moving"
     */
    protected function printAwb(Order $order): void
    {
        if ($order->status !== OrderStatus::UnprintAwb) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment;

        if (!$shipment || empty($shipment->tracking_number)) {
            abort(403, 'No AWB / tracking number');
        }

        // Mark printed (if you have column)
        if (schema_has_column('orders', 'awb_printed_at')) {
            $order->awb_printed_at = now();
        }

        // Shipment event (optional)
        $shipment->update([
            'status' => 'awb_printed',
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,
            'shipment_id' => $shipment->id,
            'status' => 'awb_printed',
        ]);

        // Move order to COD pending OR online on_the_move
        $order->status = $this->isCod($order) ? OrderStatus::Pending : OrderStatus::OnTheMove;
        $order->save();
    }

    protected function markDelivered(Order $order): void
    {
        if (!in_array($order->status, [OrderStatus::Pending, OrderStatus::OnTheMove, OrderStatus::Completed], true)) {
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

        $order->update(['status' => OrderStatus::Completed]);
    }

    protected function markReturned(Order $order): void
    {
        if (!in_array($order->status, [OrderStatus::Pending, OrderStatus::OnTheMove, OrderStatus::Completed], true)) {
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

        $order->update(['status' => OrderStatus::Returned]);
    }

    protected function reject(Order $order): void
    {
        // can reject from Draft/Approved/UnprintAwb only (adjust if you want)
        if (!in_array($order->status, [OrderStatus::Draft, OrderStatus::Approved, OrderStatus::UnprintAwb], true)) {
            abort(403, 'Invalid transition');
        }

        $order->update(['status' => OrderStatus::Rejected]);
    }

    protected function cancel(Order $order): void
    {
        // optional cancel action
        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Returned], true)) {
            abort(403, 'Invalid transition');
        }

        $order->update(['status' => OrderStatus::Cancelled]);
    }

    // -----------------------------
    // AWB actions
    // -----------------------------

    protected function cancelAwb(Order $order): void
    {
        // Per your spec: UnprintAwb should NOT have cancel-awb button
        // But guard it anyway
        if ($order->status === OrderStatus::UnprintAwb) {
            abort(403, 'Cancel AWB not allowed for Unprint AWB status');
        }

        if (!in_array($order->status, [OrderStatus::Pending, OrderStatus::OnTheMove, OrderStatus::Completed], true)) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment;

        if (!$shipment || empty($shipment->tracking_number)) {
            abort(403, 'No AWB / tracking number');
        }

        $shipment->update([
            'status' => 'cancelled',
            'tracking_number' => null,
        ]);

        ShipmentEvent::create([
            'tenant_id' => $order->tenant_id,
            'shipment_id' => $shipment->id,
            'status' => 'cancelled',
        ]);

        // After cancel AWB, put it back to Approved (so you can push again)
        $order->update(['status' => OrderStatus::Approved]);
    }

    protected function reprintAwb(Order $order): void
    {
        if (!in_array($order->status, [OrderStatus::Pending, OrderStatus::OnTheMove, OrderStatus::Completed], true)) {
            abort(403, 'Invalid transition');
        }

        $shipment = $order->shipment;

        if (!$shipment || empty($shipment->tracking_number)) {
            abort(403, 'No AWB / tracking number');
        }

        // Placeholder: implement when you have label_url / awb pdf url
        Notification::make()
            ->title('Re-print AWB not implemented yet')
            ->warning()
            ->send();
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
        $order->update(['last_synced_at' => now()]);
    }

    // -----------------------------
    // Helpers
    // -----------------------------

    /**
     * Make this robust so it won't break if your column name differs.
     * Update this once you confirm your actual payment fields.
     */
    protected function isCod(Order $order): bool
    {
        // 1) boolean column
        if (isset($order->is_cod)) {
            return (bool) $order->is_cod;
        }

        // 2) payment_method column
        if (isset($order->payment_method)) {
            $pm = strtolower((string) $order->payment_method);
            return in_array($pm, ['cod', 'cash_on_delivery', 'cash-on-delivery'], true);
        }

        // 3) payment_type column
        if (isset($order->payment_type)) {
            $pt = strtolower((string) $order->payment_type);
            return in_array($pt, ['cod', 'cash_on_delivery', 'cash-on-delivery'], true);
        }

        return false; // default assume online
    }
}

/**
 * Tiny helper to avoid fatal if column doesn't exist.
 * If you don't like this, delete the awb_printed_at section above.
 */
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