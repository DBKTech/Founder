@php
    /** @var \App\Models\Order $record */
    $customer = $record->customer;
    $shipment = $record->shipment;

    $statusValue = $record->status instanceof \App\Enums\OrderStatus
        ? $record->status->value
        : strtolower((string) ($record->status ?? ''));

    $statusLabel = $record->status instanceof \App\Enums\OrderStatus
        ? $record->status->label()
        : (string) ($record->status ?? '-');

    $trackingUrl = null;
    if ($shipment?->courier_code && $shipment?->tracking_number) {
        $tn = $shipment->tracking_number;
        $trackingUrl = match (strtolower((string) $shipment->courier_code)) {
            'poslaju' => 'https://www.pos.com.my/track-trace/?track-trace-number=' . urlencode($tn),
            'jnt'     => 'https://www.jtexpress.my/tracking/' . urlencode($tn),
            'gdex'    => 'https://www.gdexpress.com/track/?consignmentno=' . urlencode($tn),
            'dhl'     => 'https://www.dhl.com/my-en/home/tracking.html?tracking-id=' . urlencode($tn),
            default   => null,
        };
    }

    $actions = \App\Support\Orders\OrderRowActions::for($record);

    $orderViewUrl = \App\Filament\App\Resources\Orders\OrderResource::getUrl('view', ['record' => $record]);
    $orderEditUrl = \App\Filament\App\Resources\Orders\OrderResource::getUrl('edit', ['record' => $record]);

    $mapStyleToFilament = function (string $style): array {
        return match ($style) {
            'primary' => ['color' => 'primary', 'outlined' => false],
            'success' => ['color' => 'success', 'outlined' => false],
            'danger' => ['color' => 'danger', 'outlined' => false],
            'muted' => ['color' => 'gray', 'outlined' => false],
            'dark' => ['color' => 'gray', 'outlined' => false],
            'danger-outline' => ['color' => 'danger', 'outlined' => true],
            'success-outline' => ['color' => 'success', 'outlined' => true],
            'warning-outline' => ['color' => 'warning', 'outlined' => true],
            default => ['color' => 'gray', 'outlined' => false],
        };
    };

    // 🔒 FINAL button styling (Filament v5 proof)
    $btnClass = 'inline-flex items-center justify-center text-sm font-medium !h-[44px] !min-h-[44px] !px-4 !py-0 !leading-none';
@endphp

<x-filament::card class="w-full">
    <div style="width:100%; overflow-x:hidden;">
        <div style="min-width:1100px; display:flex; gap:24px; align-items:flex-start;">

            {{-- NO --}}
            <div style="width:60px; color:#6b7280; font-size:14px;">
                {{ $record->id }}
            </div>

            {{-- NAME --}}
            <div style="width:360px;">
                <div style="font-weight:600; color:#2563eb;">
                    {{ $customer?->name ?? '-' }}
                </div>

                <div style="font-size:14px; margin-top:4px;">
                    Order ID: <b>{{ $record->order_no }}</b>
                </div>

                <div style="margin-top:8px;">
                    <x-filament::badge color="gray">{{ $statusLabel }}</x-filament::badge>
                </div>

                <div style="font-size:14px; margin-top:8px;">
                    {{ $customer?->phone ?? '-' }}
                </div>

                @if(!empty($customer?->address))
                    <div style="font-size:14px; margin-top:4px;">
                        {{ $customer->address }}
                    </div>
                @endif
            </div>

            {{-- PRODUCTS --}}
            <div style="width:280px;">
                <div style="font-size:14px; color:#2563eb; margin-top:6px;">
                    {{ $record->summary_items ?? '-' }}
                </div>
            </div>

            {{-- DATE & PAYMENT --}}
            <div style="width:220px;">
                <div style="font-size:14px; margin-top:6px;">
                    {{ optional($record->ordered_at)->format('d/m/Y h:i A') ?? '-' }}
                </div>

                <div style="margin-top:10px;">
                    <x-filament::badge color="gray">
                        RM{{ number_format($record->total ?? 0, 2) }}
                    </x-filament::badge>
                </div>
            </div>

            {{-- SHIPMENT --}}
            <div style="width:240px;">
                <div style="font-size:14px;">CT: <b>{{ $shipment?->courier_code ?? '-' }}</b></div>
                <div style="font-size:14px; margin-top:6px;">ST: <b>{{ $shipment?->status ?? '-' }}</b></div>
                <div style="font-size:14px; margin-top:6px;">
                    TN:
                    @if($trackingUrl)
                        <a href="{{ $trackingUrl }}" target="_blank" style="color:#2563eb; text-decoration:underline;">
                            {{ $shipment?->tracking_number }}
                        </a>
                    @else
                        <b>{{ $shipment?->tracking_number ?? '-' }}</b>
                    @endif
                </div>
            </div>

            {{-- ACTIONS --}}
            <div style="width:360px;">
                <div style="display:flex; flex-direction:column; gap:8px; width:100%;">

                    @forelse($actions as $a)
                        @php
                            $btn = $mapStyleToFilament($a['style'] ?? 'muted');
                            $isDetails = ($a['key'] ?? '') === 'order-details';
                            $detailsUrl = ($statusValue === 'draft') ? $orderEditUrl : $orderViewUrl;
                        @endphp

                        <div style="width:100%;">
                            @if($isDetails)
                                <x-filament::button
                                    size="sm"
                                    class="{{ $btnClass }}"
                                    style="width:100%;"
                                    color="{{ $btn['color'] }}"
                                    :outlined="$btn['outlined']"
                                    tag="a"
                                    href="{{ $detailsUrl }}"
                                >
                                    {{ $a['label'] ?? 'Order Details' }}
                                </x-filament::button>
                            @else
                                <form
                                    method="POST"
                                    action="{{ route('app.orders.workflow.handle', ['order' => $record->id, 'action' => $a['key']]) }}"
                                    style="width:100%;"
                                    @if(!empty($a['confirm']))
                                        onsubmit="return confirm(@js($a['confirm']))"
                                    @endif
                                >
                                    @csrf
                                    <x-filament::button
                                        size="sm"
                                        class="{{ $btnClass }}"
                                        style="width:100%;"
                                        color="{{ $btn['color'] }}"
                                        :outlined="$btn['outlined']"
                                    >
                                        {{ $a['label'] }}
                                    </x-filament::button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div style="color:#9ca3af; font-size:14px;">—</div>
                    @endforelse

                </div>
            </div>

        </div>
    </div>
</x-filament::card>