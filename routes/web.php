<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\OrderWorkflowController;
use App\Http\Middleware\ResolveTenantContext;
use App\Http\Controllers\BillplzPaymentController;
use App\Http\Controllers\App\WooIntegrationController;


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

Route::middleware(['auth'])->prefix('app')->group(function () {
    Route::post('/integrations/woocommerce', [WooIntegrationController::class, 'store'])
        ->name('app.integrations.woocommerce.store');

    Route::post('/integrations/{integration}/woocommerce/test', [WooIntegrationController::class, 'test'])
        ->name('app.integrations.woocommerce.test');

    Route::post('/integrations/{integration}/woocommerce/import-recent', [WooIntegrationController::class, 'importRecent'])
        ->name('app.integrations.woocommerce.importRecent');

    Route::post('/integrations/{integration}/woocommerce/sync-updated', [WooIntegrationController::class, 'syncUpdated'])
        ->name('app.integrations.woocommerce.syncUpdated');

    Route::post('/integrations/{integration}/woocommerce/sync-single', [WooIntegrationController::class, 'syncSingle'])
        ->name('app.integrations.woocommerce.syncSingle');
});

Route::post('/payments/billplz/callback', [BillplzPaymentController::class, 'callback'])
    ->name('payments.billplz.callback');

Route::get('/payments/billplz/redirect/{order}', [BillplzPaymentController::class, 'redirect'])
    ->name('payments.billplz.redirect');

Route::get('/', function () {
    return view('welcome');
});
