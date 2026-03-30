<x-filament-panels::page>
    @php
        $order = $this->order;

        $shippingType = data_get($order, 'shipping_meta.type')
            ?? data_get($order, 'shipping.type')
            ?? data_get($order, 'shipment.meta.type')
            ?? null;

        $paymentMethod = $order->payment_method ?? null;

        $isCod = $paymentMethod === 'cod' || $shippingType === 'cod';
        $isThirdParty = $shippingType === 'third_party';
    @endphp

    <div class="space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-sm text-gray-500">
                    Order: {{ $order->order_no ?? '-' }}
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow">

            {{-- Top row --}}
            <div class="flex mt-2 justify-between items-center">
                <h2 class="font-bold text-xl">
                    {{ config('company.name') }}
                </h2>

                <div class="font-bold text-lg">
                    SHIPPING: {{ strtoupper($isCod ? 'COD' : 'NORMAL') }}
                </div>
            </div>

            {{-- Tenant --}}
            <div class="mt-4">
                <p class="font-bold">
                    {{ $order->tenant->name ?? '-' }}
                </p>

                <div class="mt-2 space-y-1 text-sm">
                    <p>{{ config('company.address_line_1') }}</p>
                    <p>{{ config('company.address_line_2') }}</p>
                    <p>{{ config('company.address_line_3') }}</p>
                </div>
            </div>

            {{-- Divider --}}
            <div class="border-t my-4"></div>

            {{-- Shipping To --}}
            <div>
                <div class="mb-2 text-xl font-semibold uppercase text-gray-500">
                    Shipping to
                </div>

                <div class="space-y-1 mt-3 text-sm">
                    <div class="text-base font-bold mb-2">
                        {{ $order->customer?->name ?? '-' }}
                    </div>

                    <div>
                        {{ data_get($order, 'shipment.meta.address_1', '-') }}
                    </div>

                    <div>
                        {{ trim((data_get($order, 'shipment.meta.postcode', '') . ' ' . data_get($order, 'shipment.meta.city', ''))) ?: '-' }}
                    </div>

                    <div>
                        {{ data_get($order, 'shipment.meta.state', '-') }}
                    </div>

                    <div>
                        {{ $order->customer?->phone ?? '-' }}
                    </div>

                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mt-10 mb-5">

                {{-- LEFT: Items --}}
                <div class="lg:col-span-2 rounded-xl border bg-white">
                    <div class="p-4 font-semibold border-b">Items</div>

                    <div class="divide-y">
                        @forelse($order->items as $item)
                            @php
                                $qty = (int) ($item->qty ?? 0);
                                $unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);
                                $lineTotal = (float) ($item->line_total ?? ($qty * $unitPrice));
                            @endphp

                            <div class="p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-3">
                                        @if($item->product?->primary_image_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($item->product->primary_image_path) }}"
                                                class="h-12 w-12 rounded object-cover">
                                        @endif

                                        <div>
                                            <div class="font-medium">{{ $item->title }}</div>
                                            <div class="text-sm text-gray-500">
                                                Qty: {{ $qty }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right text-sm">
                                        <div>
                                            Unit Price:
                                            <span class="font-medium">
                                                RM {{ number_format($unitPrice, 2) }}
                                            </span>
                                        </div>

                                        <div class="mt-1">
                                            Total:
                                            <span class="font-semibold">
                                                RM {{ number_format($lineTotal, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-sm text-gray-500">
                                No items found.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- RIGHT: Summary --}}
                <div class="rounded-xl border bg-white">
                    <div class="p-4 font-semibold border-b">Order Summary</div>

                    @php
                        $subtotal = (float) ($order->subtotal ?? $order->items->sum(fn($item) => (float) ($item->line_total ?? (($item->qty ?? 0) * ($item->unit_price ?? $item->price ?? 0)))));
                        $tax = (float) ($order->tax_total ?? 0);
                        $shipping = (float) ($order->shipping_total ?? 0);
                        $discount = (float) ($order->discount_total ?? 0);
                        $grandTotal = $subtotal + $tax + $shipping - $discount;
                    @endphp

                    <div class="p-4 space-y-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span>Courier:</span>
                            <span class="text-right">
                                Pos Malaysia
                                / {{ strtoupper($isCod ? 'COD' : 'NORMAL') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-t pt-4">
                            <span>Sub Total:</span>
                            <span>RM {{ number_format($subtotal, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>SST (0%):</span>
                            <span>RM {{ number_format($tax, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>Shipping Price:</span>
                            <span>RM {{ number_format($shipping, 2) }}</span>
                        </div>

                        @if($discount > 0)
                            <div class="flex items-center justify-between">
                                <span>Discount:</span>
                                <span>- RM {{ number_format($discount, 2) }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between border-t pt-4 text-base font-bold">
                            <span>Total:</span>
                            <span>RM {{ number_format($grandTotal, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COD layout --}}
            @if($isCod)
                <div class="space-y-3 rounded-xl border p-4">
                    <div class="font-semibold">COD Details</div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium">Notes (Appear on AWB)</label>
                            <textarea class="mt-1 w-full rounded-lg border p-2" rows="4"></textarea>
                        </div>
                        <div>
                            <label class="text-sm font-medium">HQ Reference (Not appear on AWB)</label>
                            <textarea class="mt-1 w-full rounded-lg border p-2" rows="4"></textarea>
                        </div>
                    </div>

                    <div class="space-y-2 rounded-lg border bg-gray-50 p-4">
                        <div class="text-sm font-semibold">Price You Sell to Customer</div>
                        <input class="w-48 rounded-lg border p-2"
                            value="{{ number_format((float) ($order->total ?? 0), 2) }}">
                        <div class="text-xs text-gray-500">
                            This amount will be printed on consignment and collected by postman.
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-filament::button>
                            Submit Order
                        </x-filament::button>
                    </div>
                </div>
            @else
                {{-- Normal / Self collect / Third party --}}
                <div class="space-y-4 rounded-xl border p-4 bg-white">
                    <div class="font-semibold">Payment Methods</div>

                    @if($isThirdParty)
                        <div>
                            <label class="text-sm font-medium">Third Party Platform</label>
                            <select class="mt-1 w-full rounded-lg border p-2">
                                <option value="">Please select</option>
                                <option value="shopee">Shopee</option>
                                <option value="tiktok">TikTok</option>
                                <option value="lazada">Lazada</option>
                                <option value="others">Others</option>
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <button type="button" wire:click="selectPaymentMethod('fpx')"
                            class="rounded-lg border p-3 text-sm font-semibold transition
                    {{ $selectedPaymentMethod === 'fpx' ? 'border-black bg-gray-100' : 'border-gray-300 bg-white hover:bg-gray-50' }}">
                            FPX
                        </button>

                        <button type="button" wire:click="selectPaymentMethod('card')"
                            class="rounded-lg border p-3 text-sm font-semibold transition
                    {{ $selectedPaymentMethod === 'card' ? 'border-black bg-gray-100' : 'border-gray-300 bg-white hover:bg-gray-50' }}">
                            Card / VISA
                        </button>

                        <button type="button" wire:click="selectPaymentMethod('wallet')"
                            class="rounded-lg border p-3 text-sm font-semibold transition
                    {{ $selectedPaymentMethod === 'wallet' ? 'border-black bg-gray-100' : 'border-gray-300 bg-white hover:bg-gray-50' }}">
                            Wallet
                        </button>

                        <button type="button" wire:click="selectPaymentMethod('bank_transfer')"
                            class="rounded-lg border p-3 text-sm font-semibold transition
                    {{ $selectedPaymentMethod === 'bank_transfer' ? 'border-black bg-gray-100' : 'border-gray-300 bg-white hover:bg-gray-50' }}">
                            Bank Transfer
                        </button>
                    </div>

                    @if($selectedPaymentMethod === 'fpx')
                        <div class="rounded-lg border bg-gray-50 p-4 text-sm text-gray-700">
                            You selected <span class="font-semibold">FPX</span>. User will proceed with online banking payment
                            flow.
                        </div>
                    @endif

                    @if($selectedPaymentMethod === 'card')
                        <div class="rounded-lg border bg-gray-50 p-4 text-sm text-gray-700">
                            You selected <span class="font-semibold">Card / VISA</span>. User will proceed with card payment
                            flow.
                        </div>
                    @endif

                    @if($selectedPaymentMethod === 'wallet')
                        <div class="rounded-lg border bg-gray-50 p-4 text-sm text-gray-700">
                            You selected <span class="font-semibold">Wallet</span>. Payment will be deducted from the user's
                            system wallet balance.
                        </div>
                    @endif

                    @if($selectedPaymentMethod === 'bank_transfer')
                        <div class="rounded-lg border bg-gray-50 p-4 space-y-4">
                            <div class="font-semibold">HQ Bank Account</div>

                            <div class="space-y-1 text-sm">
                                <div><span class="font-medium">Bank:</span> {{ config('company.bank_name') }}</div>
                                <div><span class="font-medium">Account Name:</span> {{ config('company.bank_account_name') }}
                                </div>
                                <div><span class="font-medium">Account No:</span> {{ config('company.bank_account_no') }}</div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium">Reference No</label>
                                <input type="text" wire:model.defer="bankTransferReference"
                                    class="w-full rounded-lg border px-3 py-2 text-sm"
                                    placeholder="Enter transfer reference number">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium">Upload Proof of Payment</label>
                                <input type="file" wire:model="bankTransferProof"
                                    class="w-full rounded-lg border px-3 py-2 text-sm">

                                <div class="mt-1 text-xs text-gray-500">
                                    Upload screenshot, image, or PDF proof of transfer.
                                </div>

                                @error('bankTransferProof')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end">
                        <x-filament::button type="button" wire:click="submitPayment">
                            Continue Payment
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </div>
</x-filament-panels::page>