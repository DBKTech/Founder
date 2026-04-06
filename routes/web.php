<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\OrderWorkflowController;
use App\Http\Middleware\ResolveTenantContext;
use App\Http\Controllers\BillplzPaymentController;
use App\Http\Controllers\App\WooIntegrationController;
use App\Services\Integrations\WooCommerce\WooSyncService;
use App\Models\Integration;
use App\Services\Integrations\WooCommerce\WooClient;
use App\Models\IntegrationLog;


Route::middleware(['auth'])
    ->prefix('app/workflow')
    ->name('app.')
    ->group(function () {
        Route::post(
            '/orders/{order}/{action}',
            [OrderWorkflowController::class, 'handle']
        )->name('orders.workflow.handle');
    });

Route::middleware(['auth', ResolveTenantContext::class])
    ->prefix('app/workflow')
    ->name('app.')
    ->group(function () {
        Route::post('/orders/{order}/{action}', [OrderWorkflowController::class, 'handle'])
            ->name('orders.workflow.handle');
    });

Route::middleware(['auth'])
    ->prefix('app')
    ->name('app.')
    ->group(function () {
        Route::post('/integrations/woocommerce', [WooIntegrationController::class, 'store'])
            ->name('integrations.woocommerce.store');

        Route::post('/integrations/{integration}/woocommerce/test', [WooIntegrationController::class, 'test'])
            ->name('integrations.woocommerce.test');

        Route::post('/integrations/{integration}/woocommerce/import-recent', [WooIntegrationController::class, 'importRecent'])
            ->name('integrations.woocommerce.importRecent');

        Route::post('/integrations/{integration}/woocommerce/sync-updated', [WooIntegrationController::class, 'syncUpdated'])
            ->name('integrations.woocommerce.syncUpdated');

        Route::post('/integrations/{integration}/woocommerce/sync-single', [WooIntegrationController::class, 'syncSingle'])
            ->name('integrations.woocommerce.syncSingle');
    });

Route::get('/debug-create-woo', function () {
    $tenantId = auth()->user()->tenant_id;

    $integration = Integration::create([
        'tenant_id' => $tenantId,
        'platform' => 'woocommerce',
        'store_name' => 'Cleosafe Care',
        'store_url' => 'https://cleosafecare.com.my/',
        'api_key' => 'ck_137aee2bfa37904f2ea49b7035e31e275d564068',
        'api_secret' => 'cs_5d357becf537d07324da38b511feeca70eaf8167',
        'status' => 'disconnected',
    ]);

    return $integration;
})->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/debug-woo/create', function () {
        $tenantId = auth()->user()->tenant_id;

        $integration = Integration::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'platform' => 'woocommerce',
                'store_url' => 'https://cleosafecare.com.my/',
            ],
            [
                'store_name' => 'Cleosafe Care',
                'api_key' => 'ck_137aee2bfa37904f2ea49b7035e31e275d564068',
                'api_secret' => 'cs_5d357becf537d07324da38b511feeca70eaf8167',
                'status' => 'disconnected',
            ]
        );

        return response()->json([
            'message' => 'Integration created',
            'integration_id' => $integration->id,
            'integration' => $integration,
        ]);
    });

    Route::get('/debug-woo/test/{integration}', function (Integration $integration, WooSyncService $service) {
        abort_unless((int) $integration->tenant_id === (int) auth()->user()->tenant_id, 403);

        return response()->json($service->test($integration));
    });

    Route::get('/debug-woo/import/{integration}', function (Integration $integration, WooSyncService $service) {
        abort_unless((int) $integration->tenant_id === (int) auth()->user()->tenant_id, 403);

        return response()->json($service->importRecentOrders($integration, 20));
    });
});

Route::get('/debug-woo/show/{integration}', function (Integration $integration) {
    return response()->json([
        'id' => $integration->id,
        'tenant_id' => $integration->tenant_id,
        'store_url' => $integration->store_url,
        'platform' => $integration->platform,
        'status' => $integration->status,
        'has_key' => filled($integration->api_key),
        'has_secret' => filled($integration->api_secret),
    ]);
})->middleware('auth');

Route::get('/debug-woo/latest-log', function () {
    return IntegrationLog::latest()->first();
})->middleware('auth');

Route::get('/debug-woo/raw-orders-test/{integration}', function (Integration $integration) {
    abort_unless((int) $integration->tenant_id === (int) auth()->user()->tenant_id, 403);

    $client = new WooClient($integration);

    try {
        $data = $client->getOrders([
            'per_page' => 1,
            'orderby' => 'date',
            'order' => 'desc',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
})->middleware('auth');

Route::post('/payments/billplz/callback', [BillplzPaymentController::class, 'callback'])
    ->name('payments.billplz.callback');

Route::get('/payments/billplz/redirect/{order}', [BillplzPaymentController::class, 'redirect'])
    ->name('payments.billplz.redirect');

Route::get('/', function () {
    return view('welcome');
});
