<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\Integrations\WooCommerce\WooSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PollWooOrdersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $integrationId)
    {
    }

    public function handle(WooSyncService $service): void
    {
        $integration = Integration::find($this->integrationId);

        if (! $integration || $integration->platform !== 'woocommerce' || $integration->status === 'disabled') {
            return;
        }

        $service->syncUpdatedOrders($integration);
    }
}