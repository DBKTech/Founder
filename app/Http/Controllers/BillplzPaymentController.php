<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\BillplzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillplzPaymentController extends Controller
{
    public function callback(Request $request)
    {
        $billId = $request->input('id');

        if (! $billId) {
            return response()->json(['message' => 'Missing bill id'], 422);
        }

        $payment = Payment::query()
            ->where('gateway', 'billplz')
            ->where('gateway_reference', $billId)
            ->latest('id')
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $bill = app(BillplzService::class)->getBill($billId);
        $isPaid = (bool) ($bill['paid'] ?? false);
        $paidAt = $isPaid ? now() : null;

        DB::transaction(function () use ($payment, $bill, $isPaid, $paidAt, $request, $billId) {
            $payment->update([
                'status' => $isPaid ? 'paid' : 'failed',
                'gateway_payment_id' => $bill['payment_id'] ?? null,
                'paid_at' => $isPaid ? ($payment->paid_at ?? $paidAt) : null,
                'meta' => array_merge($payment->meta ?? [], [
                    'bill' => $bill,
                    'callback_payload' => $request->all(),
                ]),
            ]);

            $payment->order?->update([
                'payment_status' => $isPaid ? 'paid' : 'failed',
                'payment_gateway' => 'billplz',
                'payment_ref' => $billId,
                'paid_at' => $isPaid ? ($payment->order->paid_at ?? $paidAt) : null,
            ]);
        });

        return response()->json(['ok' => true]);
    }

    public function redirect(Order $order, Request $request)
    {
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('gateway', 'billplz')
            ->latest('id')
            ->first();

        $isPaid = false;

        if ($payment?->gateway_reference) {
            $billId = $payment->gateway_reference;

            $bill = app(BillplzService::class)->getBill($billId);
            $isPaid = (bool) ($bill['paid'] ?? false);
            $paidAt = $isPaid ? now() : null;

            DB::transaction(function () use ($payment, $order, $bill, $isPaid, $paidAt, $request, $billId) {
                $payment->update([
                    'status' => $isPaid ? 'paid' : 'pending',
                    'paid_at' => $isPaid ? ($payment->paid_at ?? $paidAt) : $payment->paid_at,
                    'gateway_payment_id' => $bill['payment_id'] ?? ($payment->gateway_payment_id ?? null),
                    'meta' => array_merge($payment->meta ?? [], [
                        'bill' => $bill,
                        'redirect_payload' => $request->all(),
                    ]),
                ]);

                $order->update([
                    'payment_status' => $isPaid ? 'paid' : 'pending',
                    'payment_gateway' => 'billplz',
                    'payment_ref' => $billId,
                    'paid_at' => $isPaid ? ($order->paid_at ?? $paidAt) : $order->paid_at,
                ]);
            });
        }

        if ($isPaid) {
            return redirect(\App\Filament\App\Pages\OrderSummary::getUrl([
                'order' => $order,
            ], panel: 'app'));
        }

        return redirect(\App\Filament\App\Pages\OrderCheckout::getUrl([
            'order' => $order,
            'payment_status' => 'cancelled',
        ], panel: 'app'));
    }
}