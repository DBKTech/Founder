<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\Integrations\WooCommerce\WooSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WooIntegrationController extends Controller
{
    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $data = $request->validate([
            'store_name' => ['nullable', 'string', 'max:255'],
            'store_url' => ['required', 'url', 'max:255'],
            'api_key' => ['required', 'string'],
            'api_secret' => ['required', 'string'],
        ]);

        $integration = Integration::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'platform' => 'woocommerce',
                'store_url' => rtrim($data['store_url'], '/'),
            ],
            [
                'store_name' => $data['store_name'] ?? null,
                'api_key' => $data['api_key'],
                'api_secret' => $data['api_secret'],
                'status' => 'disconnected',
            ]
        );

        return response()->json([
            'message' => 'WooCommerce store saved.',
            'integration_id' => $integration->id,
        ]);
    }

    public function test(Integration $integration, WooSyncService $service)
    {
        $this->authorizeTenant($integration);

        $result = $service->test($integration);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function importRecent(Integration $integration, WooSyncService $service)
    {
        $this->authorizeTenant($integration);

        $result = $service->importRecentOrders($integration, 20);

        return response()->json([
            'message' => 'Recent Woo orders imported.',
            'result' => $result,
        ]);
    }

    public function syncUpdated(Integration $integration, WooSyncService $service)
    {
        $this->authorizeTenant($integration);

        $result = $service->syncUpdatedOrders($integration);

        return response()->json([
            'message' => 'Woo orders synced.',
            'result' => $result,
        ]);
    }

    public function syncSingle(Request $request, Integration $integration, WooSyncService $service)
    {
        $this->authorizeTenant($integration);

        $data = $request->validate([
            'woo_order_id' => ['required'],
        ]);

        $result = $service->syncSingleOrder($integration, $data['woo_order_id']);

        return response()->json([
            'message' => 'Single Woo order synced.',
            'result' => $result,
        ]);
    }

    protected function authorizeTenant(Integration $integration): void
    {
        abort_unless((int) $integration->tenant_id === (int) auth()->user()->tenant_id, 403);
    }
}