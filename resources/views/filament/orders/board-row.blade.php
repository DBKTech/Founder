@php
    /** @var \App\Models\Order $record */
    $customer = $record->customer;
    $shipment = $record->shipment;

    $trackingUrl = null;
    if ($shipment?->courier_code && $shipment?->tracking_number) {
        $tn = $shipment->tracking_number;
        $trackingUrl = match ($shipment->courier_code) {
            'poslaju' => 'https://www.pos.com.my/track-trace/?track-trace-number=' . urlencode($tn),
            'jnt' => 'https://www.jtexpress.my/tracking/' . urlencode($tn),
            'gdex' => 'https://www.gdexpress.com/track/?consignmentno=' . urlencode($tn),
            'dhl' => 'https://www.dhl.com/my-en/home/tracking.html?tracking-id=' . urlencode($tn),
            default => null,
        };
    }
@endphp

<x-filament::card class="w-full">
    <div style="width:100%; overflow-x:hidden;">
        <div style="min-width:1100px; display:flex; gap:24px; align-items:flex-start;">

            {{-- NO --}}
            <div style="width:60px; flex:0 0 auto; color:#6b7280; font-size:14px;">
                {{ $record->id }}
            </div>

            {{-- NAME --}}
            <div style="width:360px; flex:0 0 auto;">
                <div style="font-weight:600; color: var(--primary-600, #2563eb);">
                    {{ $customer?->name ?? '-' }}
                </div>

                <div style="font-size:14px; color:#374151; margin-top:4px;">
                    Order ID: <span style="font-weight:600;">{{ $record->order_no }}</span>
                </div>

                <div style="font-size:14px; color:#374151; margin-top:4px;">
                    {{ $customer?->phone ?? '-' }}
                </div>

                @if(!empty($customer?->address))
                    <div style="font-size:14px; color:#374151; margin-top:4px;">
                        {{ $customer->address }}
                    </div>
                @endif
            </div>

            {{-- PRODUCTS --}}
            <div style="width:280px; flex:0 0 auto;">
                <div style="font-size:14px; color: var(--primary-600, #2563eb); margin-top:6px;">
                    {{ $record->summary_items ?? '-' }}
                </div>
            </div>

            {{-- DATE & PAYMENT --}}
            <div style="width:220px; flex:0 0 auto;">
                <div style="font-size:14px; color:#111827; margin-top:6px;">
                    {{ optional($record->ordered_at)->format('d/m/Y h:i A') ?? '-' }}
                </div>

                <div style="margin-top:10px;">
                    <x-filament::badge color="gray">
                        RM{{ number_format($record->total ?? 0, 2) }}
                    </x-filament::badge>
                </div>
            </div>

            {{-- SHIPMENT DETAILS --}}
            <div style="width:240px; flex:0 0 auto;">
                <div style="font-size:14px; color:#111827; margin-top:6px;">
                    CT: <span style="font-weight:600;">{{ $shipment?->courier_code ?? '-' }}</span>
                </div>
                <div style="font-size:14px; color:#111827; margin-top:6px;">
                    ST: <span style="font-weight:600;">{{ $shipment?->status ?? '-' }}</span>
                </div>
                <div style="font-size:14px; color:#111827; margin-top:6px;">
                    TN: <span style="font-weight:600;">{{ $shipment?->tracking_number ?? '-' }}</span>
                </div>

                @if($trackingUrl)
                    <div style="margin-top:12px;">
                        <x-filament::button
                            size="sm"
                            color="success"
                            tag="a"
                            href="{{ $trackingUrl }}"
                            target="_blank"
                        >
                            Open Tracking
                        </x-filament::button>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-filament::card>
