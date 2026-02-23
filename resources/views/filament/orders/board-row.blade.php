@php
    /** @var \App\Models\Order $record */
    $customer = $record->customer;
    $shipment = $record->shipment;

    $statusValue = $record->status instanceof \App\Enums\OrderStatus
        ? $record->status->value
        : (string) ($record->status ?? '');

    $statusLabel = $record->status instanceof \App\Enums\OrderStatus
        ? $record->status->label()
        : (string) ($record->status ?? '-');

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

    // ----------------------------
    // FounderHQ-style ACTIONS (like your picture 2)
    // ----------------------------
    $hasShipment = (bool) $shipment;
    $hasTracking = !empty($shipment?->tracking_number);

    // normalize shipment status string
    $shipmentStatus = strtolower((string) ($shipment?->status ?? ''));
    $courierLabel = $shipment?->courier_name
        ?? $shipment?->courier_title
        ?? $shipment?->courier_code
        ?? null;

    /**
     * Each action:
     *  - key: route action segment
     *  - label: button text
     *  - color: filament color
     *  - outlined: bool (for outline buttons)
     *  - icon: optional heroicon name
     */
    $actions = [];

    // 1) Approval / Reject (for draft/pending payment)
    if (in_array($statusValue, ['draft', 'pending_payment'], true)) {
        $actions[] = [
            'key' => 'approval',
            'label' => 'Approval',
            'color' => 'warning',
            'outlined' => false,
            'icon' => null,
        ];

        $actions[] = [
            'key' => 'reject',
            'label' => 'Reject Order',
            'color' => 'danger',
            'outlined' => true,
            'icon' => null,
        ];
    }

    // 2) Processing -> Push To Courier (no AWB yet / or still allow push)
    if ($statusValue === 'processing') {
        $pushLabel = $courierLabel
            ? "Push To {$courierLabel}" . ($shipment?->oauth_provider ? ' (OAuth2)' : '')
            : 'Push To Courier';

        $actions[] = [
            'key' => 'push-courier',
            'label' => $pushLabel,
            'color' => 'success',
            'outlined' => false,
            'icon' => null,
        ];

        $actions[] = [
            'key' => 'reject',
            'label' => 'Reject Order',
            'color' => 'danger',
            'outlined' => true,
            'icon' => null,
        ];
    }

    // 3) Shipment/AWB actions (when shipment exists)
    if ($hasShipment) {
        // If got tracking, allow AWB related actions
        if ($hasTracking) {
            // Cancel AWB (only if not delivered/returned)
            if (!in_array($shipmentStatus, ['delivered', 'returned', 'cancelled'], true)) {
                $actions[] = [
                    'key' => 'cancel-awb',
                    'label' => 'Cancel AWB',
                    'color' => 'danger',
                    'outlined' => true,
                    'icon' => null,
                ];
            }

            // Mark delivered / returned for in-progress statuses
            if (in_array($shipmentStatus, ['pending_collection', 'shipped', 'in_transit', 'out_for_delivery', 'normal', 'pending'], true)) {
                $actions[] = [
                    'key' => 'mark-delivered',
                    'label' => 'Mark As Delivered',
                    'color' => 'success',
                    'outlined' => true,
                    'icon' => null,
                ];

                $actions[] = [
                    'key' => 'mark-returned',
                    'label' => 'Mark As Returned',
                    'color' => 'warning',
                    'outlined' => true,
                    'icon' => null,
                ];
            }

            // Reprint + Delivery Order always if have AWB
            $actions[] = [
                'key' => 'reprint-awb',
                'label' => 'Re-Print AWB',
                'color' => 'gray',
                'outlined' => false,
                'icon' => null,
            ];

            $actions[] = [
                'key' => 'delivery-order',
                'label' => 'Delivery Order',
                'color' => 'gray',
                'outlined' => false,
                'icon' => null,
            ];
        } else {
            // Shipment exists but no tracking yet -> show push
            $pushLabel = $courierLabel ? "Push To {$courierLabel}" : 'Push To Courier';
            $actions[] = [
                'key' => 'push-courier',
                'label' => $pushLabel,
                'color' => 'success',
                'outlined' => false,
                'icon' => null,
            ];
        }
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

                <div style="margin-top:8px;">
                    <x-filament::badge color="gray">
                        {{ $statusLabel }}
                    </x-filament::badge>
                </div>

                <div style="font-size:14px; color:#374151; margin-top:8px;">
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
                    TN:
                    @if($trackingUrl)
                        <a href="{{ $trackingUrl }}" target="_blank" style="font-weight:600; color: var(--primary-600, #2563eb); text-decoration: underline;">
                            {{ $shipment?->tracking_number ?? '-' }}
                        </a>
                    @else
                        <span style="font-weight:600;">{{ $shipment?->tracking_number ?? '-' }}</span>
                    @endif
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

            {{-- ACTIONS (like picture 2) --}}
            <div style="width:320px; flex:0 0 auto;">
                <div style="display:flex; flex-direction:column; gap:10px; margin-top:6px;">
                    @forelse($actions as $a)
                        <form
                            method="POST"
                            action="{{ route('app.orders.workflow.handle', ['order' => $record->id, 'action' => $a['key']]) }}"
                        >
                            @csrf

                            <x-filament::button
                                size="sm"
                                class="w-full"
                                color="{{ $a['color'] }}"
                                :outlined="$a['outlined']"
                                :icon="$a['icon']"
                            >
                                {{ $a['label'] }}
                            </x-filament::button>
                        </form>
                    @empty
                        <div style="color:#9ca3af; font-size:14px;">
                            —
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-filament::card>
