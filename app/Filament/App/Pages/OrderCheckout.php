<?php

namespace App\Filament\App\Pages;

use App\Models\Order;
use App\Models\Wallet;
use App\Models\Payment;
use App\Services\Payments\WalletPaymentService;
use RuntimeException;
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

    public float $walletBalance = 0;
    public int $orderId;
    public ?Order $order = null;

    public ?string $selectedPaymentMethod = null;
    public ?string $bankTransferReference = null;
    public $bankTransferProof = null;
    public float $checkoutTotal = 0;

    public function mount(Order $order): void
    {
        $this->orderId = (int) $order->getKey();
        $this->order = $order->load(['tenant', 'customer', 'shipment', 'items.product']);

        $this->selectedPaymentMethod = $order->payment_method;
        $this->bankTransferReference = $order->payment_ref;
        $this->checkoutTotal = (float) $this->resolveOrderTotal($order);
        $this->refreshWalletBalance();

        if (request('payment_status') === 'cancelled') {
            Notification::make()
                ->title('Payment was not completed')
                ->body('The payment process was cancelled or not completed. Please try again or choose another payment method.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    public function getOrderProperty(): Order
    {
        return Order::query()
            ->with(['tenant', 'customer', 'shipment', 'items.product'])
            ->findOrFail($this->orderId);
    }

    protected function startGatewayPayment(Order $order, string $method): void
    {
        $amount = $this->resolveOrderTotal($order);

        /** @var \App\Services\Payments\BillplzService $billplz */
        $billplz = app(\App\Services\Payments\BillplzService::class);

        $paymentChannel = $billplz->getPaymentChannelForMethod($method);

        // For card, only use direct channel if active in Billplz account
        if ($method === 'card' && $paymentChannel && !$billplz->isGatewayActive($paymentChannel)) {
            Notification::make()
                ->title('Card payment is currently unavailable.')
                ->body('Please choose FPX, wallet, or bank transfer for now.')
                ->warning()
                ->send();

            return;
        }

        $bill = $billplz->createBill([
            'name' => $order->customer?->name ?? auth()->user()?->name ?? 'Customer',
            'email' => $order->customer?->email ?? auth()->user()?->email,
            'mobile' => $order->customer?->phone ?? null,
            'amount' => $amount,
            'description' => 'Payment for order ' . ($order->order_no ?? $order->id),
            'order_no' => $order->order_no ?? (string) $order->id,
            'callback_url' => route('payments.billplz.callback'),
            'redirect_url' => route('payments.billplz.redirect', ['order' => $order->id]),
            'payment_channel' => $paymentChannel,
        ]);

        $checkoutUrl = $billplz->buildCheckoutUrl($bill, $paymentChannel);

        DB::transaction(function () use ($order, $method, $amount, $bill, $checkoutUrl, $paymentChannel): void {
            $order->update([
                'payment_method' => $method,
                'payment_status' => 'pending',
                'payment_gateway' => 'billplz',
                'payment_ref' => $bill['id'] ?? null,
            ]);

            Payment::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'gateway' => 'billplz',
                    'method' => $method,
                ],
                [
                    'tenant_id' => $order->tenant_id,
                    'user_id' => auth()->id(),
                    'status' => 'pending',
                    'currency' => 'MYR',
                    'amount' => $amount,
                    'reference' => $order->order_no ?? (string) $order->id,
                    'gateway_reference' => $bill['id'] ?? null,
                    'gateway_url' => $checkoutUrl,
                    'meta' => [
                        'bill' => $bill,
                        'payment_channel' => $paymentChannel,
                    ],
                ]
            );
        });

        redirect()->away($checkoutUrl);
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

        match ($this->selectedPaymentMethod) {
            'bank_transfer' => $this->submitBankTransfer($order),
            'wallet' => $this->payWithWallet($order),
            'fpx', 'card' => $this->startGatewayPayment($order, $this->selectedPaymentMethod),
            default => Notification::make()
                ->title('Invalid payment method selected.')
                ->danger()
                ->send(),
        };
    }

    protected function submitBankTransfer(Order $order): void
    {
        $this->validate([
            'bankTransferReference' => ['nullable', 'string', 'max:100'],
            'bankTransferProof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $this->bankTransferProof->store('payment-proofs', 'public');
        $amount = $this->resolveOrderTotal($order);

        DB::transaction(function () use ($order, $path, $amount): void {
            $order->update([
                'payment_method' => 'bank_transfer',
                'payment_status' => 'awaiting_verification',
                'payment_gateway' => 'manual',
                'payment_ref' => $this->bankTransferReference,
                'bank_transfer_proof_path' => $path,
            ]);

            Payment::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'method' => 'bank_transfer',
                'gateway' => 'manual',
                'status' => 'awaiting_verification',
                'currency' => 'MYR',
                'amount' => $amount,
                'reference' => $this->bankTransferReference,
                'bank_transfer_proof_path' => $path,
                'meta' => [
                    'source' => 'bank_transfer_checkout',
                ],
            ]);
        });

        Notification::make()
            ->title('Bank transfer proof uploaded successfully.')
            ->success()
            ->send();

        $this->redirect(OrderSummary::getUrl([
            'order' => $order,
        ], panel: 'app'));
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

        $total = $this->resolveOrderTotal($order);

        if ($total <= 0) {
            Notification::make()
                ->title('Invalid order total.')
                ->danger()
                ->send();

            return;
        }

        try {
            app(WalletPaymentService::class)->pay($order, $user->id, $total);

            Notification::make()
                ->title('Payment made successfully using wallet.')
                ->success()
                ->send();

            $this->redirect(OrderSummary::getUrl([
                'order' => $order,
            ], panel: 'app'));
        } catch (RuntimeException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Wallet payment failed.')
                ->danger()
                ->send();
        }
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

    protected function refreshWalletBalance(): void
    {
        $user = auth()->user();

        if (!$user) {
            $this->walletBalance = 0;
            return;
        }

        $this->walletBalance = (float) (
            Wallet::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('user_id', $user->id)
                ->value('balance') ?? 0
        );
    }

    public function getView(): string
    {
        return 'filament.app.pages.order-checkout';
    }
}