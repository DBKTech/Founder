@props([
    'type' => 'row',
    'record' => null,
])

@if($type === 'header')
    <div class="orders-board-grid">
        <div class="orders-col orders-col--select"></div>
        <div class="orders-col orders-col--no">No</div>
        <div class="orders-col orders-col--name">Customer Details</div>
        <div class="orders-col orders-col--products">Products</div>
        <div class="orders-col orders-col--datetime">Order Date</div>
        <div class="orders-col orders-col--payment">Payment Details</div>
        <div class="orders-col orders-col--actions">Actions</div>
    </div>
@else
    @php
        $shipment = $record->shipment;

        $customerName = $record->display_customer_name ?? $record->customer?->name ?? '-';
        $customerPhone = $record->display_customer_phone ?? $record->customer?->phone ?? '-';
        $customerAddress = $record->display_customer_address ?? $record->customer?->address ?? '-';

        $statusLabel = $record->status instanceof \App\Enums\OrderStatus
            ? $record->status->label()
            : (string) ($record->status ?? '-');

        $statusColor = match ($record->status?->value ?? strtolower((string) $record->status)) {
            'completed' => 'success',
            'approved' => 'primary',
            'pending' => 'warning',
            'onthemove' => 'info',
            'unprint_awb', 'unprintawb' => 'gray',
            'returned', 'rejected', 'cancelled' => 'danger',
            default => 'gray',
        };

        $orderedAt = optional($record->ordered_at)->format('d/m/Y h:i A') ?? '-';

        $paymentMethod = $record->payment_method ?: ($record->payment_gateway ?: '-');
        $paymentStatus = $record->payment_status ?: '-';

        $deliveryMethod = match ($shipment?->courier_code) {
            'sendparcelpro' => 'Pos Malaysia',
            default => $shipment?->courier_code ?: data_get($record->meta, 'shipping_lines.0.method_title', '-'),
        };

        $totalFormatted = 'RM' . number_format((float) ($record->total ?? 0), 2);
        $shippingFormatted = 'RM' . number_format((float) ($record->shipping_total ?? 0), 2);

        $actions = \App\Support\Orders\OrderRowActions::for($record);

        $orderViewUrl = \App\Filament\App\Resources\Orders\OrderResource::getUrl('view', ['record' => $record]);
        $orderEditUrl = \App\Filament\App\Resources\Orders\OrderResource::getUrl('edit', ['record' => $record]);

        $statusValue = $record->status instanceof \App\Enums\OrderStatus
            ? $record->status->value
            : strtolower((string) ($record->status ?? ''));

        $paymentStatusColor = match (strtolower((string) $paymentStatus)) {
            'paid', 'success', 'completed' => 'success',
            'pending', 'unpaid', 'processing', 'awaiting_verification' => 'warning',
            'failed', 'cancelled', 'rejected', 'refunded' => 'danger',
            default => 'gray',
        };

        $shipmentStatus = strtolower((string) ($shipment?->status ?? ''));
        $shipmentTracking = $shipment?->tracking_number ?: '-';

        $shipmentStatusLabel = match ($shipmentStatus) {
            'created' => 'Created',
            'awb_printed' => 'AWB Printed',
            'picked_up' => 'Picked Up',
            'in_transit' => 'In Transit',
            'out_for_delivery' => 'Out For Delivery',
            'delivered' => 'Delivered',
            'returned' => 'Returned',
            'cancelled' => 'Cancelled',
            default => $shipment?->status ? str($shipment->status)->replace('_', ' ')->title() : '-',
        };

        $shipmentStatusColor = match ($shipmentStatus) {
            'created', 'awb_printed' => 'gray',
            'picked_up', 'in_transit', 'out_for_delivery' => 'info',
            'delivered' => 'success',
            'returned', 'cancelled' => 'danger',
            default => 'gray',
        };

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
    @endphp

    <div class="orders-board-grid">
        <div class="orders-col orders-col--select">
            <input type="checkbox" class="orders-select-checkbox">
        </div>

        <div class="orders-col orders-col--no">
            {{ $record->id }}
        </div>

        <div class="orders-col orders-col--name">
            <div class="orders-name">{{ $customerName }}</div>
            <div class="orders-meta">Order ID: <strong>{{ $record->order_no }}</strong></div>
            <div class="orders-meta">{{ $customerPhone }}</div>
            <div class="orders-meta orders-address">{{ $customerAddress }}</div>

            <div class="orders-badge-wrap">
                <x-filament::badge :color="$statusColor" size="sm">
                    {{ $statusLabel }}
                </x-filament::badge>
            </div>
        </div>

        <div class="orders-col orders-col--products">
            {{ $record->summary_items ?? '-' }}
        </div>

        <div class="orders-col orders-col--datetime">
            {{ $orderedAt }}
        </div>

        <div class="orders-col orders-col--payment">
            <div class="orders-detail"><span>Delivery</span><span>{{ $deliveryMethod }}</span></div>
<div class="orders-detail">
    <span>Tracking</span>

    @if($shipmentTracking !== '-')
        <button
            type="button"
            class="orders-tracking-copy"
            onclick="navigator.clipboard.writeText(@js($shipmentTracking)); this.innerText='Copied'; setTimeout(() => this.innerText='Click to copy', 1200)"
            title="{{ $shipmentTracking }}"
        >
            Click to copy
        </button>
    @else
        <span class="orders-tracking-empty">-</span>
    @endif
</div>          <div class="orders-detail">
                <span>Shipment</span>
                <x-filament::badge :color="$shipmentStatusColor" size="sm">
                    {{ $shipmentStatusLabel }}
                </x-filament::badge>
            </div>
            <div class="orders-detail"><span>Payment</span><span>{{ strtoupper((string) $paymentMethod) }}</span></div>
            <div class="orders-detail"><span>Total</span><span>{{ $totalFormatted }}</span></div>
            <div class="orders-detail"><span>Shipping</span><span>{{ $shippingFormatted }}</span></div>
            <div class="orders-detail">
                <span>Status</span>
                <x-filament::badge :color="$paymentStatusColor" size="sm">
                    {{ $paymentStatus }}
                </x-filament::badge>
            </div>
        </div>

        <div class="orders-col orders-col--actions">
            <div class="orders-actions-stack">
                @forelse($actions as $a)
                    @php
                        $btn = $mapStyleToFilament($a['style'] ?? 'muted');
                        $isDetails = ($a['key'] ?? '') === 'order-details';
                        $detailsUrl = ($statusValue === 'draft') ? $orderEditUrl : $orderViewUrl;
                        $openInNewTab = in_array(($a['key'] ?? ''), ['print-awb', 'reprint-awb'], true);
                    @endphp

                    @if($isDetails)
                        <x-filament::button
                            size="sm"
                            class="orders-action-btn"
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
                            class="orders-action-form"
                            @if($openInNewTab)
                                target="_blank"
                            @endif
                            @if(!empty($a['confirm']))
                                onsubmit="return confirm(@js($a['confirm']))"
                            @endif
                        >
                            @csrf

                            <x-filament::button
                                type="submit"
                                size="sm"
                                class="orders-action-btn"
                                color="{{ $btn['color'] }}"
                                :outlined="$btn['outlined']"
                            >
                                {{ $a['label'] }}
                            </x-filament::button>
                        </form>
                    @endif
                @empty
                    <div>—</div>
                @endforelse
            </div>
        </div>
    </div>
@endif