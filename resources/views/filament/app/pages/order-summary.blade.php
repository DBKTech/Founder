@php
    /** @var \App\Filament\App\Pages\OrderSummary $this */
    $order = $this->order;

    $shippingType = data_get($order, 'shipment.meta.type');
    $isCod = $shippingType === 'cod';

    $subtotal = (float) ($order->subtotal ?? 0);
    $tax = (float) ($order->tax_total ?? $order->tax ?? 0);
    $shipping = (float) ($order->shipping_total ?? $order->shipping_fee ?? 0);
    $discount = (float) ($order->discount_total ?? 0);
    $grandTotal = $this->resolveOrderTotal($order);

    $paymentMethod = strtoupper(str_replace('_', ' ', $order->payment_method ?? 'unpaid'));
    $paymentStatus = strtoupper(str_replace('_', ' ', $order->payment_status ?? 'unpaid'));
@endphp

<style>
    .invoice-print-root {
        width: 100%;
    }

    .invoice-page {
        width: 100%;
        max-width: 920px;
        margin: 0 auto;
        background: #fff;
    }

    .invoice-section,
    .invoice-table,
    .invoice-totals,
    .invoice-footer {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .invoice-table table,
    .invoice-table thead,
    .invoice-table tbody,
    .invoice-table tr,
    .invoice-table td,
    .invoice-table th {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    @media screen {
        body {
            background: #f3f4f6;
        }

        .invoice-outer {
            padding-bottom: 24px;
        }

        .invoice-page {
            min-height: auto;
        }
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            height: auto !important;
            overflow: visible !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body * {
            visibility: hidden !important;
        }

        .invoice-print-root,
        .invoice-print-root * {
            visibility: visible !important;
        }

        .invoice-print-root {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .invoice-outer,
        .invoice-outer>div,
        .invoice-page {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .invoice-outer {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }

        .invoice-page {
            width: 100% !important;
            max-width: 100% !important;
            min-height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            page-break-before: auto !important;
            page-break-after: auto !important;
            page-break-inside: avoid !important;
            break-before: auto !important;
            break-after: auto !important;
            break-inside: avoid !important;
        }

        .no-print,
        header,
        aside,
        nav,
        .fi-topbar,
        .fi-sidebar,
        .fi-header,
        .fi-breadcrumbs,
        .fi-page-sub-navigation {
            display: none !important;
        }

        .fi-page,
        .fi-main,
        .fi-main-ctn,
        .fi-page-content,
        .fi-section,
        .fi-section-content,
        .fi-simple-page,
        main {
            margin: 0 !important;
            padding: 0 !important;
            min-height: auto !important;
            max-width: 100% !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        table,
        tr,
        td,
        th {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        img {
            max-height: 32px !important;
            max-width: 32px !important;
        }

        .text-3xl {
            font-size: 18px !important;
            line-height: 1.1 !important;
        }

        .text-base {
            font-size: 12px !important;
        }

        .text-sm {
            font-size: 10px !important;
            line-height: 1.3 !important;
        }

        .text-xs {
            font-size: 9px !important;
            line-height: 1.2 !important;
        }

        .print-tight-gap {
            gap: 12px !important;
        }

        .print-tight-space>*+* {
            margin-top: 2px !important;
        }

        .print-borderless {
            border: none !important;
            box-shadow: none !important;
        }

        .print-p-0 {
            padding: 0 !important;
        }

        .print-p-2 {
            padding: 8px !important;
        }

        .print-px-2 {
            padding-left: 8px !important;
            padding-right: 8px !important;
        }

        .print-py-2 {
            padding-top: 8px !important;
            padding-bottom: 8px !important;
        }

        .print-mt-2 {
            margin-top: 8px !important;
        }

        .print-mt-3 {
            margin-top: 12px !important;
        }

        .print-pt-2 {
            padding-top: 8px !important;
        }

        .print-pb-2 {
            padding-bottom: 8px !important;
        }

        .print-grid-tight {
            gap: 12px !important;
        }
    }
</style>

<x-filament-panels::page>
    <div class="invoice-print-root">
        <div class="invoice-outer mx-auto max-w-5xl space-y-5">

            {{-- Top actions --}}
            <div class="no-print mb-5 flex justify-end">
                <button type="button" onclick="window.print()"
                    class="rounded-lg border px-4 py-2 text-sm font-semibold">
                    Print Invoice
                </button>
            </div>
            
            {{-- Invoice --}}
            <div class="invoice-page rounded-2xl border bg-white p-6 shadow-sm print-borderless print-p-0">

                {{-- Header --}}
                <div class="invoice-section border-b pb-4 print-pb-2">
                    <div class="text-3xl font-bold tracking-tight">
                        INVOICE
                    </div>

                    <div class="mt-3 text-sm text-gray-700 print-tight-space">
                        <div class="text-base font-semibold text-black">
                            {{ config('company.name') }}
                        </div>

                        <div class="font-medium">
                            {{ $order->tenant->name ?? '-' }}
                        </div>

                        <div>{{ config('company.address_line_1') }}</div>
                        <div>{{ config('company.address_line_2') }}</div>
                        <div>{{ config('company.address_line_3') }}</div>

                        @if(config('company.phone'))
                            <div>{{ config('company.phone') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Address section --}}
                <div
                    class="invoice-section grid grid-cols-1 gap-4 border-b py-4 md:grid-cols-2 print-grid-tight print-py-2">
                    <div class="rounded-xl border p-4 print-p-2">
                        <div class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                            Ship To
                        </div>

                        <div class="space-y-1 text-sm text-gray-800 print-tight-space">
                            <div class="font-semibold text-black">
                                {{ $order->customer?->name ?? '-' }}
                            </div>
                            <div>{{ $order->customer?->phone ?? '-' }}</div>
                            <div>{{ data_get($order, 'shipment.meta.address_1', '-') }}</div>

                            @if(data_get($order, 'shipment.meta.address_2'))
                                <div>{{ data_get($order, 'shipment.meta.address_2') }}</div>
                            @endif

                            <div>
                                {{ trim((data_get($order, 'shipment.meta.postcode', '') . ' ' . data_get($order, 'shipment.meta.city', ''))) ?: '-' }}
                            </div>
                            <div>{{ data_get($order, 'shipment.meta.state', '-') }}</div>
                        </div>
                    </div>

                    <div class="rounded-xl border p-4 print-p-2">
                        <div class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                            Invoice Info
                        </div>

                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-800">
                            <div class="font-medium text-black">Order No</div>
                            <div>{{ $order->order_no ?? ('#' . $order->id) }}</div>

                            <div class="font-medium text-black">Date</div>
                            <div>{{ optional($order->ordered_at ?? $order->created_at)->format('d M Y, h:i A') }}</div>

                            <div class="font-medium text-black">Payment</div>
                            <div>{{ $paymentMethod }}</div>

                            <div class="font-medium text-black">Status</div>
                            <div>{{ $paymentStatus }}</div>

                            <div class="font-medium text-black">Shipping</div>
                            <div>{{ strtoupper($isCod ? 'COD' : 'NORMAL') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Items and summary --}}
                <div class="grid grid-cols-1 gap-4 py-4 lg:grid-cols-3 print-grid-tight print-py-2">

                    {{-- Items --}}
                    <div class="invoice-table lg:col-span-2">
                        <div class="overflow-hidden rounded-xl border">
                            <table class="min-w-full border-collapse text-sm">
                                <thead>
                                    <tr
                                        class="border-b bg-gray-50 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                        <th class="px-3 py-3 print-px-2 print-py-2">Item</th>
                                        @if($copyType === 'seller')
                                            <th class="px-3 py-3 print-px-2 print-py-2">SKU</th>
                                        @endif
                                        <th class="px-3 py-3 text-center print-px-2 print-py-2">Qty</th>
                                        <th class="px-3 py-3 text-right print-px-2 print-py-2">Unit Price</th>
                                        <th class="px-3 py-3 text-right print-px-2 print-py-2">Amount</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($order->items as $item)
                                        @php
                                            $qty = (int) ($item->qty ?? $item->quantity ?? 0);
                                            $unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);
                                            $lineTotal = (float) ($item->line_total ?? ($qty * $unitPrice));
                                        @endphp

                                        <tr class="border-b align-top last:border-b-0">
                                            <td class="px-3 py-3 print-px-2 print-py-2">
                                                <div class="flex items-start gap-2">
                                                    @if($item->product?->primary_image_path)
                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item->product->primary_image_path) }}"
                                                            class="h-10 w-10 shrink-0 rounded border object-cover">
                                                    @endif

                                                    <div class="min-w-0">
                                                        <div class="font-medium leading-snug text-black">
                                                            {{ $item->title }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            @if($copyType === 'seller')
                                                <td class="px-3 py-3 text-gray-700 print-px-2 print-py-2">
                                                    {{ $item->product?->sku ?? '-' }}
                                                </td>
                                            @endif

                                            <td class="px-3 py-3 text-center text-gray-700 print-px-2 print-py-2">
                                                {{ $qty }}
                                            </td>

                                            <td
                                                class="px-3 py-3 text-right text-gray-700 whitespace-nowrap print-px-2 print-py-2">
                                                RM {{ number_format($unitPrice, 2) }}
                                            </td>

                                            <td
                                                class="px-3 py-3 text-right font-semibold text-black whitespace-nowrap print-px-2 print-py-2">
                                                RM {{ number_format($lineTotal, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $copyType === 'seller' ? 5 : 4 }}"
                                                class="px-4 py-5 text-center text-sm text-gray-500">
                                                No items found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="invoice-totals">
                        <div class="rounded-xl border bg-gray-50 p-4 text-sm print-p-2">
                            <div class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-500">
                                Summary
                            </div>

                            <div class="space-y-2 print-tight-space">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-medium text-black whitespace-nowrap">
                                        RM {{ number_format($subtotal, 2) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-600">Tax</span>
                                    <span class="font-medium text-black whitespace-nowrap">
                                        RM {{ number_format($tax, 2) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-600">Shipping</span>
                                    <span class="font-medium text-black whitespace-nowrap">
                                        RM {{ number_format($shipping, 2) }}
                                    </span>
                                </div>

                                @if($discount > 0)
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-600">Discount</span>
                                        <span class="font-medium text-black whitespace-nowrap">
                                            - RM {{ number_format($discount, 2) }}
                                        </span>
                                    </div>
                                @endif

                                <div
                                    class="mt-3 flex items-center justify-between gap-3 border-t pt-3 text-base font-bold print-mt-2 print-pt-2">
                                    <span>Total</span>
                                    <span class="whitespace-nowrap">
                                        RM {{ number_format($grandTotal, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="invoice-footer mt-4 border-t pt-4 text-sm text-gray-600 print-mt-3 print-pt-2">
                    @if($copyType === 'buyer')
                        <div>
                            Thank you for your order.
                        </div>
                    @endif

                    @if($copyType === 'seller')
                        <div class="space-y-2 print-tight-space">
                            <div class="font-semibold text-black">Internal Seller Notes</div>

                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                <div>
                                    <span class="font-medium">Order Status:</span>
                                    {{ $order->status ?? '-' }}
                                </div>

                                <div>
                                    <span class="font-medium">Payment Ref:</span>
                                    {{ $order->payment_ref ?? '-' }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>