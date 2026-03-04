<x-filament::page>
    <div class="space-y-6">

        {{-- Top bar --}}
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

            <div class="flex items-center gap-2">
                {{-- Optional: search UI only (wire:model if you add a property) --}}
                <div class="hidden md:block">
                    <input type="text" placeholder="Search product…"
                        class="w-72 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-300 focus:ring-0" />
                </div>

                {{-- Cart count badge --}}
                @php $cartCount = array_sum($this->cart ?? []); @endphp
                <div
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm">
                    <span class="font-medium">Cart</span>
                    <span
                        class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-gray-900 px-2 text-xs font-semibold text-white">
                        {{ $cartCount }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            {{-- Products grid --}}
            <div class="lg:col-span-8 xl:col-span-9">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->listings() as $listing)
                        @php $product = $listing->product; @endphp

                        <div
                            class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                            <div class="relative aspect-[4/3] bg-gray-50">
                                @if($product?->primary_image_path)
                                    <img class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                        src="{{ \Illuminate\Support\Facades\Storage::url($product->primary_image_path) }}"
                                        alt="{{ $product->name }}" loading="lazy" />
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-sm text-gray-400">
                                        No image
                                    </div>
                                @endif

                                {{-- Badge --}}
                                <div
                                    class="absolute left-3 top-3 inline-flex items-center rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-gray-700 shadow-sm">
                                    Published
                                </div>
                            </div>

                            <div class="space-y-3 p-4">
                                <div class="min-h-[2.5rem]">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $product?->name ?? 'Missing product' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        SKU: {{ $product?->sku ?? '-' }}
                                    </div>
                                </div>

                                <div class="flex items-end justify-between gap-3">
                                    <div>
                                        <div class="text-xs text-gray-500">Price</div>
                                        <div class="text-lg font-semibold text-gray-900">
                                            RM {{ number_format($product?->price ?? 0, 2) }}
                                        </div>
                                    </div>

                                    {{-- Add / Qty controls --}}
                                    @php $qty = $product ? ($this->cart[$product->id] ?? 0) : 0; @endphp

                                    @if($qty <= 0)
                                        <button type="button"
                                            class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800"
                                            wire:click="addToCart({{ $product->id }})" @disabled(!$product)>
                                            Add
                                        </button>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                class="h-9 w-9 rounded-xl border border-gray-200 bg-white text-lg font-semibold text-gray-800 hover:bg-gray-50"
                                                wire:click="setQty({{ $product->id }}, {{ $qty - 1 }})">
                                                −
                                            </button>

                                            <div class="min-w-[2.5rem] text-center text-sm font-semibold">
                                                {{ $qty }}
                                            </div>

                                            <button type="button"
                                                class="h-9 w-9 rounded-xl border border-gray-200 bg-white text-lg font-semibold text-gray-800 hover:bg-gray-50"
                                                wire:click="setQty({{ $product->id }}, {{ $qty + 1 }})">
                                                +
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($this->listings()->isEmpty())
                    <div
                        class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                        No published listings yet. Publish products from Platform → Marketplace Listings.
                    </div>
                @endif
            </div>

            {{-- Cart sidebar --}}
            <div class="lg:col-span-4 xl:col-span-3">
                <div class="sticky top-6 space-y-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="text-base font-semibold">Your Cart</div>
                        <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-800"
                            wire:click="clearCart" @disabled(empty($this->cart))>
                            Clear
                        </button>
                    </div>

                    @php
                        $items = collect($this->cart)
                            ->map(function ($qty, $productId) {
                                return [
                                    'product' => \App\Models\Product::find($productId),
                                    'qty' => (int) $qty,
                                ];
                            })
                            ->filter(fn($row) => $row['product']);
                        $subtotal = $items->sum(fn($row) => ($row['product']->price ?? 0) * $row['qty']);
                    @endphp

                    <div class="space-y-2">
                        @forelse($items as $row)
                            <div
                                class="flex items-start justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50 p-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gray-900">
                                        {{ $row['product']->name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        RM {{ number_format($row['product']->price ?? 0, 2) }} • Qty {{ $row['qty'] }}
                                    </div>
                                </div>
                                <div class="text-sm font-semibold text-gray-900">
                                    RM {{ number_format(($row['product']->price ?? 0) * $row['qty'], 2) }}
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">
                                Cart is empty
                            </div>
                        @endforelse
                    </div>

                    <div class="border-t border-gray-100 pt-3">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">Subtotal</div>
                            <div class="text-base font-semibold text-gray-900">
                                RM {{ number_format($subtotal, 2) }}
                            </div>
                        </div>

                        <div class="mt-3 text-xs text-gray-500">
                            Checkout button is at the top (header actions).
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>