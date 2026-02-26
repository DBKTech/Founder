<x-filament::page>
    <div class="mb-4 text-sm text-gray-600">
        Products: {{ $this->products()->count() }}
        | Tenant: {{ auth()->user()->tenant_id }}
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-4">
        @foreach ($this->products() as $product)
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="aspect-[4/3] w-full overflow-hidden rounded-lg bg-gray-50 flex items-center justify-center">
                    @if($product->primary_image_path)
                        <img class="h-full w-full object-cover"
                            src="{{ \Illuminate\Support\Facades\Storage::url($product->primary_image_path) }}"
                            alt="{{ $product->name }}">
                    @else
                        <div class="text-sm text-gray-400">No image</div>
                    @endif
                </div>

                <div class="mt-3 font-semibold">{{ $product->name }}</div>
                <div class="mt-2 flex gap-2">
                    <div class="rounded border px-3 py-1 text-sm">(AP) RM
                        {{ number_format($product->price_ap ?? $product->price, 2) }}</div>
                    <div class="rounded border px-3 py-1 text-sm">(SP) RM
                        {{ number_format($product->price_sp ?? $product->price, 2) }}</div>
                </div>

                <div class="mt-3 flex items-center justify-between gap-2">
                    <button type="button" class="w-full rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white"
                        wire:click="addToCart({{ $product->id }})">
                        Add To Cart
                    </button>
                </div>

                @php $qty = $this->cart[$product->id] ?? 0; @endphp

                @if($qty > 0)
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-sm text-gray-600">Qty</span>
                        <input type="number" min="0" class="w-24 rounded border px-2 py-1 text-sm" value="{{ $qty }}"
                            wire:change="setQty({{ $product->id }}, $event.target.value)" />
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-filament::page>