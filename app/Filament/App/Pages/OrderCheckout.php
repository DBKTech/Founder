<?php

namespace App\Filament\App\Pages;

use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\App\Pages\OrderSummary;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use UnitEnum;

class OrderCheckout extends Page
{
    use WithFileUploads;

    protected static BackedEnum|string|null $navigationIcon = null;
    protected static UnitEnum|string|null $navigationGroup = null;
    protected static ?string $navigationLabel = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'order-checkout/{order}';

    public int $orderId;

    public ?string $selectedPaymentMethod = null;
    public ?string $bankTransferReference = null;
    public $bankTransferProof = null;

    public function mount(Order $order): void
    {
        $this->orderId = (int) $order->getKey();

        // Optional: preload existing selected payment method
        $this->selectedPaymentMethod = $order->payment_method;
        $this->bankTransferReference = $order->payment_ref;
    }

    public function getOrderProperty(): Order
    {
        return Order::query()
            ->with(['tenant', 'customer', 'shipment', 'items.product'])
            ->findOrFail($this->orderId);
    }

    public function selectPaymentMethod(string $method): void
    {
        $allowed = ['fpx', 'card', 'wallet', 'bank_transfer'];

        if (!in_array($method, $allowed, true)) {
            Notification::make()
                ->title('Invalid payment method selected.')
                ->danger()
                ->send();

            return;
        }

        $this->selectedPaymentMethod = $method;
    }

    public function submitPayment(): void
    {
        $order = $this->order;

        if (!$this->selectedPaymentMethod) {
            Notification::make()
                ->title('Please select a payment method.')
                ->danger()
                ->send();

            return;
        }

        if ($this->selectedPaymentMethod === 'bank_transfer') {
            $this->submitBankTransfer($order);
            return;
        }

        if ($this->selectedPaymentMethod === 'wallet') {
            $this->payWithWallet($order);
            return;
        }

        if ($this->selectedPaymentMethod === 'fpx') {
            $order->update([
                'payment_method' => 'fpx',
                'payment_status' => 'pending',
                'payment_gateway' => 'pending_gateway',
            ]);

            Notification::make()
                ->title('FPX payment flow is not connected yet.')
                ->success()
                ->send();

            $this->redirect(OrderSummary::getUrl(['order' => $order]));
            return;
        }

        if ($this->selectedPaymentMethod === 'card') {
            $order->update([
                'payment_method' => 'card',
                'payment_status' => 'pending',
                'payment_gateway' => 'pending_gateway',
            ]);

            Notification::make()
                ->title('Card / VISA payment flow is not connected yet.')
                ->success()
                ->send();
                
            $this->redirect(OrderSummary::getUrl(['order' => $order]));
            return;
        }
    }

    protected function submitBankTransfer(Order $order): void
    {
        $this->validate([
            'bankTransferReference' => ['nullable', 'string', 'max:100'],
            'bankTransferProof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $this->bankTransferProof->store('payment-proofs', 'public');

        $order->update([
            'payment_method' => 'bank_transfer',
            'payment_status' => 'awaiting_verification',
            'payment_ref' => $this->bankTransferReference,
            'bank_transfer_proof_path' => $path,
            // Uncomment kalau nak tukar status order sekali:
            // 'status' => 'pending_payment',
        ]);

        Notification::make()
            ->title('Bank transfer proof uploaded successfully.')
            ->success()
            ->send();

        $this->redirect(OrderSummary::getUrl(['order' => $order]));
    }

    protected function payWithWallet(Order $order): void
    {
        $user = auth()->user();

        if (!$user) {
            Notification::make()
                ->title('User not authenticated.')
                ->danger()
                ->send();

            return;
        }

        $wallet = Wallet::firstOrCreate(
            [
                'tenant_id' => $order->tenant_id,
                'user_id' => $user->id,
            ],
            [
                'balance' => 0,
            ]
        );

        $total = $this->resolveOrderTotal($order);

        if ($total <= 0) {
            Notification::make()
                ->title('Invalid order total.')
                ->danger()
                ->send();

            return;
        }

        if ((float) $wallet->balance < $total) {
            Notification::make()
                ->title('Wallet balance is not enough.')
                ->danger()
                ->send();

            return;
        }

        DB::transaction(function () use ($wallet, $order, $total): void {
            $wallet->decrement('balance', $total);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'order_id' => $order->id,
                'type' => 'debit',
                'amount' => $total,
                'reference' => 'ORDER-' . $order->id,
                'remarks' => 'Payment for order ' . ($order->order_no ?? $order->id),
            ]);

            $order->update([
                'payment_method' => 'wallet',
                'payment_status' => 'paid',
                'paid_at' => now(),
                // Uncomment kalau nak tukar status order sekali:
                // 'status' => 'approved',
            ]);
        });

        Notification::make()
            ->title('Payment made successfully using wallet.')
            ->success()
            ->send();

        $this->redirect(OrderSummary::getUrl(['order' => $order]));
    }

    protected function resolveOrderTotal(Order $order): float
    {
        $possibleTotals = [
            $order->total ?? null,
            $order->total_amount ?? null,
            $order->grand_total ?? null,
        ];

        foreach ($possibleTotals as $value) {
            if ($value !== null) {
                return (float) $value;
            }
        }

        $subtotal = (float) ($order->subtotal ?? 0);
        $shipping = (float) ($order->shipping_total ?? $order->shipping_fee ?? 0);
        $tax = (float) ($order->tax_total ?? $order->tax ?? 0);
        $discount = (float) ($order->discount_total ?? 0);

        return max(0, $subtotal + $shipping + $tax - $discount);
    }

    public function getView(): string
    {
        return 'filament.app.pages.order-checkout';
    }
}