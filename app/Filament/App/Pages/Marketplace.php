<?php

namespace App\Filament\App\Pages;

use App\Models\Product;
use App\Services\MarketplaceOrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class Marketplace extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static UnitEnum|string|null $navigationGroup = 'Marketplace';
    protected static ?string $title = 'Marketplace';

    public array $cart = []; // [productId => qty]

    // ✅ Filament v5: use method instead of static $view
    public function getView(): string
    {
        return 'filament.app.pages.marketplace';
    }

    public function mount(): void
    {
        $this->cart = session()->get($this->cartKey(), []);
    }

    protected function cartKey(): string
    {
        return 'marketplace_cart_tenant_' . auth()->user()->tenant_id . '_user_' . auth()->id();
    }

    public function products(): Collection
    {
        return Product::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function addToCart(int $productId): void
    {
        $this->cart[$productId] = ($this->cart[$productId] ?? 0) + 1;
        $this->persistCart();

        Notification::make()
            ->title('Added to cart')
            ->success()
            ->send();
    }

    public function setQty(int $productId, int $qty): void
    {
        $qty = max(0, $qty);

        if ($qty === 0) {
            unset($this->cart[$productId]);
        } else {
            $this->cart[$productId] = $qty;
        }

        $this->persistCart();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->persistCart();
    }

    protected function persistCart(): void
    {
        session()->put($this->cartKey(), $this->cart);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkout')
                ->label('Checkout')
                ->icon('heroicon-o-credit-card')
                ->disabled(fn () => empty($this->cart))
                ->form([
                    TextInput::make('customer_name')->required(),
                    TextInput::make('customer_phone')->required(),

                    TextInput::make('address_1')->label('Address')->required(),
                    TextInput::make('city')->required(),
                    TextInput::make('state')->required(),
                    TextInput::make('postcode')->required(),

                    Select::make('price_tier')
                        ->options(['AP' => 'AP', 'SP' => 'SP'])
                        ->default('SP')
                        ->required(),

                    Select::make('payment_method')
                        ->options([
                            'bank_transfer' => 'Bank Transfer',
                            'qr' => 'QR',
                            'cod' => 'COD',
                        ])
                        ->default('bank_transfer')
                        ->required(),

                    TextInput::make('payment_ref')
                        ->label('Payment Ref (optional)')
                        ->helperText('Example: last 4 digits / reference no.')
                        ->maxLength(100),
                ])
                ->action(function (array $data) {
                    $items = collect($this->cart)
                        ->map(fn ($qty, $productId) => [
                            'product_id' => (int) $productId,
                            'qty' => (int) $qty,
                        ])
                        ->values()
                        ->all();

                    $payload = [
                        'tenant_id' => auth()->user()->tenant_id,
                        'placed_by_user_id' => auth()->id(),
                        'price_tier' => $data['price_tier'],
                        'payment_method' => $data['payment_method'],
                        'payment_ref' => $data['payment_ref'] ?? null,
                        'customer' => [
                            'name' => $data['customer_name'],
                            'phone' => $data['customer_phone'],
                        ],
                        'shipping' => [
                            'name' => $data['customer_name'],
                            'phone' => $data['customer_phone'],
                            'address_1' => $data['address_1'],
                            'city' => $data['city'],
                            'state' => $data['state'],
                            'postcode' => $data['postcode'],
                        ],
                        'items' => $items,
                    ];

                    $order = app(MarketplaceOrderService::class)->create($payload);

                    $this->clearCart();

                    Notification::make()
                        ->title("Order created: {$order->order_no}")
                        ->success()
                        ->send();

                    $this->redirectRoute('filament.app.resources.orders.index');
                }),
        ];
    }
}