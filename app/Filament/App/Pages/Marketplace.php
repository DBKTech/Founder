<?php

namespace App\Filament\App\Pages;

use App\Models\Product;
use App\Models\Customer;
use App\Services\MarketplaceOrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;
use App\Models\MarketplaceListing;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

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

    public function listings(): \Illuminate\Database\Eloquent\Collection
    {
        return MarketplaceListing::query()
            ->published()
            ->with('product')
            ->orderByDesc('published_at')
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

    protected function isEastMalaysia(?string $postcode, ?string $state): bool
    {
        $postcode = preg_replace('/\D/', '', (string) $postcode); // digits only
        $prefix2 = strlen($postcode) >= 2 ? (int) substr($postcode, 0, 2) : null;

        // Postcode-based (standard Malaysia)
        if ($prefix2 !== null) {
            if ($prefix2 === 87)
                return true; // Labuan
            if ($prefix2 >= 88 && $prefix2 <= 91)
                return true; // Sabah
            if ($prefix2 >= 93 && $prefix2 <= 98)
                return true; // Sarawak
        }

        // Fallback by state string
        $state = strtoupper(trim((string) $state));
        return in_array($state, ['SABAH', 'SARAWAK', 'LABUAN'], true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkout')
                ->label('Checkout')
                ->icon('heroicon-o-credit-card')
                ->disabled(fn() => empty($this->cart))
                ->slideOver()
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Checkout')
                ->form([
                    Section::make('BUYER INFORMATION')
                        ->schema([
                            // ✅ Small box inside checkout
                            Section::make('Entry Mode')
                                ->compact()
                                ->schema([
                                    ToggleButtons::make('entry_mode')
                                        ->label('')
                                        ->options([
                                            'manual' => 'Manual',
                                            'fast_entry' => 'Fast Entry',
                                            'myself' => 'Myself',
                                        ])
                                        ->inline()
                                        ->default('manual')
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                            // whenever mode changes -> reset source selector
                                            $set('customer_id', null);

                                            // If switch to manual: clear auto-filled fields (optional)
                                            if ($state === 'manual') {
                                                $set('name', null);
                                                $set('phone', null);
                                                $set('email', null);
                                                $set('address', null);
                                                $set('postcode', null);
                                                $set('city', null);
                                                $set('state', null);
                                                return;
                                            }

                                            // If switch to myself: fill from logged user
                                            if ($state === 'myself') {
                                                $user = auth()->user();
                                                $set('name', $user?->name);
                                                $set('email', $user?->email);
                                                // kalau user ada phone:
                                                // $set('phone', $user?->phone);
                                
                                                return;
                                            }

                                            // fast_entry: do nothing here, wait user select customer
                                        }),

                                    // ✅ only appears when Fast Entry selected
                                    Select::make('customer_id')
                                        ->label('')
                                        ->placeholder('Search Previous Customer...')
                                        ->searchable()
                                        ->native(false)
                                        ->live()
                                        ->visible(fn(Get $get) => $get('entry_mode') === 'fast_entry')
                                        ->getSearchResultsUsing(function (string $search): array {
                                            $tenantId = auth()->user()->tenant_id;

                                            return Customer::query()
                                                ->where('tenant_id', $tenantId)
                                                ->where(function ($q) use ($search) {
                                                    $q->where('name', 'like', "%{$search}%")
                                                        ->orWhere('phone', 'like', "%{$search}%")
                                                        ->orWhere('email', 'like', "%{$search}%");
                                                })
                                                ->limit(25)
                                                ->get()
                                                ->mapWithKeys(fn(Customer $c) => [
                                                    $c->id => trim(($c->name ?? '') . ' — ' . ($c->phone ?? '')),
                                                ])
                                                ->all();
                                        })
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                            if (!$state) {
                                                return;
                                            }

                                            // guard: only fill when in fast_entry
                                            if ($get('entry_mode') !== 'fast_entry') {
                                                return;
                                            }

                                            $customer = Customer::query()
                                                ->where('tenant_id', auth()->user()->tenant_id)
                                                ->find($state);

                                            if (!$customer) {
                                                return;
                                            }

                                            $set('name', $customer->name);
                                            $set('phone', $customer->phone);
                                            $set('email', $customer->email);

                                            // If you store address fields in Customer, you can auto-fill too:
                                            // $set('address', $customer->address);
                                            // $set('postcode', $customer->postcode);
                                            // $set('city', $customer->city);
                                            // $set('state', $customer->state);
                                        }),
                                ]),

                            // ✅ Main buyer form fields (manual / auto-filled)
                            TextInput::make('name')->label('Name')->required(),

                            Grid::make(2)->schema([
                                TextInput::make('phone')->label('Phone')->tel()->required(),
                                TextInput::make('email')->label('Email')->email()->nullable(),
                            ]),

                            TextInput::make('address')->label('Address')->required(),

                            TextInput::make('postcode')
                                ->label('Postcode')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('shipping_method', null);
                                    $set('shipping_type', null);
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('city')->label('City')->required(),
                                TextInput::make('state')
                                    ->label('State')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('shipping_method', null);
                                        $set('shipping_type', null);
                                    }),
                            ]),
                        ]),

                    Section::make('SHIPPING & PAYMENT')
                        ->schema([
                            Select::make('shipping_method')
                                ->label('Shipping Method')
                                ->placeholder('Please enter postcode and state first.')
                                ->native(false)
                                ->required()
                                ->live()
                                ->disabled(fn(Get $get) => blank($get('postcode')) || blank($get('state')))
                                ->options(function (Get $get) {
                                    $isEast = $this->isEastMalaysia($get('postcode'), $get('state'));

                                    $normal = $isEast ? 10 : 0;   // Sabah/Sarawak/Labuan
                                    $cod = $isEast ? 20 : 10;  // Sabah/Sarawak/Labuan
                        
                                    return [
                                        'pos_malaysia' => "Pos Malaysia - Normal: RM {$normal}.00, COD: RM {$cod}.00",
                                        'self_collect' => 'Self Collect',
                                    ];
                                })
                                ->afterStateUpdated(function (Set $set) {
                                    $set('shipping_type', null);
                                }),

                            // Only show when Pos Malaysia selected
                            Select::make('shipping_type')
                                ->label('Shipping Type')
                                ->native(false)
                                ->placeholder('Please select')
                                ->required(fn(Get $get) => $get('shipping_method') === 'pos_malaysia')
                                ->visible(fn(Get $get) => $get('shipping_method') === 'pos_malaysia')
                                ->options([
                                    'cod' => 'Cash On Delivery',
                                    'normal' => 'Normal',
                                    'third_party' => 'Third Party Platform',
                                ]),
                        ]),
                ])
                ->action(function (array $data) {
                    $items = collect($this->cart)
                        ->map(fn($qty, $productId) => [
                            'product_id' => (int) $productId,
                            'qty' => (int) $qty,
                        ])
                        ->values()
                        ->all();

                    $paymentMethod = 'bank_transfer';
                    if (($data['shipping_method'] ?? null) === 'pos_malaysia') {
                        $paymentMethod = match ($data['shipping_type'] ?? null) {
                            'cod' => 'cod',
                            default => 'bank_transfer', // normal / third_party -> treat as non-COD
                        };
                    }

                    $payload = [
                        'tenant_id' => auth()->user()->tenant_id,
                        'placed_by_user_id' => auth()->id(),
                        'price_tier' => 'SP',
                        'payment_method' => $paymentMethod,
                        'payment_ref' => null,

                        'customer' => [
                            'name' => $data['name'],
                            'phone' => $data['phone'],
                            'email' => $data['email'] ?? null,
                        ],

                        'shipping' => [
                            'name' => $data['name'],
                            'phone' => $data['phone'],
                            'address_1' => $data['address'],
                            'city' => $data['city'],
                            'state' => $data['state'],
                            'postcode' => $data['postcode'],
                            'method' => $data['shipping_method'] ?? null,
                            'type' => $data['shipping_type'] ?? null,
                        ],

                        'shipping_payment' => $data['shipping_payment'] ?? null,
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