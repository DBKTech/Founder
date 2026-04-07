<?php

namespace App\Filament\App\Pages;

use App\Models\Integration;
use App\Services\Integrations\WooCommerce\WooSyncService;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

class WooCommerceIntegration extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'WooCommerce';

    protected static string|\UnitEnum|null $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.app.pages.woo-commerce-integration';

    public ?array $data = [];

    public ?Integration $integration = null;

    public function mount(): void
    {
        $this->integration = $this->getIntegration();

        $this->form->fill([
            'store_name' => $this->integration?->store_name,
            'store_url' => $this->integration?->store_url,
            'api_key' => $this->integration?->api_key,
            'api_secret' => $this->integration?->api_secret,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('WooCommerce Connection')
                    ->description('Connect your WooCommerce store to sync orders into this system.')
                    ->schema([
                        TextInput::make('store_name')
                            ->label('Store Name')
                            ->maxLength(255),

                        TextInput::make('store_url')
                            ->label('Store URL')
                            ->placeholder('https://yourstore.com')
                            ->url()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('api_key')
                            ->label('Consumer Key')
                            ->required()
                            ->password()
                            ->revealable(),

                        TextInput::make('api_secret')
                            ->label('Consumer Secret')
                            ->required()
                            ->password()
                            ->revealable(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getIntegration(): ?Integration
    {
        $tenantId = auth()->user()?->tenant_id;

        if (! $tenantId) {
            return null;
        }

        return Integration::query()
            ->where('tenant_id', $tenantId)
            ->where('platform', 'woocommerce')
            ->latest('id')
            ->first();
    }

    public function save(): void
    {
        $tenantId = auth()->user()?->tenant_id;

        if (! $tenantId) {
            Notification::make()
                ->title('Tenant not found.')
                ->danger()
                ->send();

            return;
        }

        $state = $this->form->getState();

        $this->integration = Integration::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'platform' => 'woocommerce',
            ],
            [
                'store_name' => $state['store_name'] ?? null,
                'store_url' => rtrim((string) $state['store_url'], '/'),
                'api_key' => $state['api_key'],
                'api_secret' => $state['api_secret'],
                'status' => $this->integration?->status ?? 'disconnected',
            ]
        );

        Notification::make()
            ->title('WooCommerce integration saved.')
            ->success()
            ->send();
    }

    public function testConnection(WooSyncService $service): void
    {
        $this->save();

        if (! $this->integration) {
            Notification::make()
                ->title('Integration not found.')
                ->danger()
                ->send();

            return;
        }

        try {
            $result = $service->test($this->integration);

            Notification::make()
                ->title($result['ok'] ? 'Connection successful.' : 'Connection failed.')
                ->body($result['message'] ?? null)
                ->color($result['ok'] ? 'success' : 'danger')
                ->send();

            $this->integration = $this->getIntegration();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Connection test failed.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function importRecentOrders(WooSyncService $service): void
    {
        $this->save();

        if (! $this->integration) {
            Notification::make()
                ->title('Integration not found.')
                ->danger()
                ->send();

            return;
        }

        try {
            $result = $service->importRecentOrders($this->integration, 20);

            Notification::make()
                ->title('Recent Woo orders imported.')
                ->body('Imported ' . ($result['count'] ?? 0) . ' orders.')
                ->success()
                ->send();

            $this->integration = $this->getIntegration();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Import failed.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function syncUpdatedOrders(WooSyncService $service): void
    {
        $this->save();

        if (! $this->integration) {
            Notification::make()
                ->title('Integration not found.')
                ->danger()
                ->send();

            return;
        }

        try {
            $result = $service->syncUpdatedOrders($this->integration);

            Notification::make()
                ->title('Woo orders synced.')
                ->body('Synced ' . ($result['count'] ?? 0) . ' updated orders.')
                ->success()
                ->send();

            $this->integration = $this->getIntegration();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Sync failed.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getStatusData(): array
    {
        $integration = $this->getIntegration();

        return [
            'status' => $integration?->status ?? 'not connected',
            'last_tested_at' => $integration?->last_tested_at?->toDateTimeString(),
            'last_synced_at' => $integration?->last_synced_at?->toDateTimeString(),
            'store_url' => $integration?->store_url,
        ];
    }
}