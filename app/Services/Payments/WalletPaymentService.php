<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletPaymentService
{
    public function pay(Order $order, int $userId, float $amount): void
    {
        DB::transaction(function () use ($order, $userId, $amount): void {
            $wallet = Wallet::where('tenant_id', $order->tenant_id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new RuntimeException('Wallet not found.');
            }

            if (! $wallet->is_active) {
                throw new RuntimeException('Wallet is inactive.');
            }

            if ((float) $wallet->balance < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $before = (float) $wallet->balance;
            $after = $before - $amount;

            $wallet->update([
                'balance' => $after,
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'order_id' => $order->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference' => 'ORDER-' . $order->id,
                'remarks' => 'Payment for order ' . ($order->order_no ?? $order->id),
                'status' => 'posted',
                'meta' => [
                    'payment_method' => 'wallet',
                ],
            ]);

            Payment::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'user_id' => $userId,
                'method' => 'wallet',
                'gateway' => 'wallet',
                'status' => 'paid',
                'currency' => 'MYR',
                'amount' => $amount,
                'reference' => 'ORDER-' . $order->id,
                'paid_at' => now(),
                'meta' => [
                    'source' => 'wallet_checkout',
                ],
            ]);

            $order->update([
                'payment_method' => 'wallet',
                'payment_status' => 'paid',
                'payment_gateway' => 'wallet',
                'payment_ref' => 'ORDER-' . $order->id,
                'paid_at' => now(),
            ]);
        });
    }
}