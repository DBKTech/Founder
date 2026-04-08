<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\OrderWorkflowController;
use App\Http\Middleware\ResolveTenantContext;
use App\Http\Controllers\BillplzPaymentController;
use App\Http\Controllers\App\WooIntegrationController;

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

Route::post('/payments/billplz/callback', [BillplzPaymentController::class, 'callback'])
    ->name('payments.billplz.callback');

Route::get('/payments/billplz/redirect/{order}', [BillplzPaymentController::class, 'redirect'])
    ->name('payments.billplz.redirect');

Route::get('/shipments/fake-label/{tracking}', function (string $tracking) {
    return response("
        <html>
            <head>
                <title>Fake AWB Label</title>
            </head>
            <body style='font-family: Arial, sans-serif; padding: 40px;'>
                <h1>Fake AWB Label</h1>
                <p><strong>Tracking Number:</strong> {$tracking}</p>
                <p><strong>Courier:</strong> SendParcel Pro (Fake)</p>
                <p><strong>Status:</strong> Created</p>
                <hr>
                <p>This is a fake label for local development.</p>
                <script>window.print();</script>
            </body>
        </html>
    ", 200, [
        'Content-Type' => 'text/html',
    ]);
})->name('shipments.fake-label');

Route::get('/', function () {
    return view('welcome');
});