<x-filament-panels::page>
    @php
        $status = $this->getStatusData();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border bg-white p-5 shadow-sm">

            {{ $this->form }}

            <div class="mt-6 flex flex-wrap gap-3">
                <x-filament::button wire:click="save">
                    Save
                </x-filament::button>

                <x-filament::button color="gray" wire:click="testConnection">
                    Test Connection
                </x-filament::button>

                <x-filament::button color="success" wire:click="importRecentOrders">
                    Import Recent Orders
                </x-filament::button>

                <x-filament::button color="warning" wire:click="syncUpdatedOrders">
                    Sync Updated Orders
                </x-filament::button>
            </div>
        </div>

        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <h3 class="text-base font-bold">Connection Status</h3>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border p-4">
                    <div class="text-xs text-gray-500">Status</div>
                    <div class="mt-1 text-sm font-semibold">{{ $status['status'] ?? 'not connected' }}</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-xs text-gray-500">Store URL</div>
                    <div class="mt-1 text-sm font-semibold break-all">{{ $status['store_url'] ?? '-' }}</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-xs text-gray-500">Last Tested</div>
                    <div class="mt-1 text-sm font-semibold">{{ $status['last_tested_at'] ?? '-' }}</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-xs text-gray-500">Last Synced</div>
                    <div class="mt-1 text-sm font-semibold">{{ $status['last_synced_at'] ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>