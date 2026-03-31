<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\OrderWorkflowController;
use App\Http\Middleware\ResolveTenantContext;
use App\Http\Controllers\BillplzPaymentController;


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

Route::post('/payments/billplz/callback', [BillplzPaymentController::class, 'callback'])
    ->name('payments.billplz.callback');

Route::get('/payments/billplz/redirect/{order}', [BillplzPaymentController::class, 'redirect'])
    ->name('payments.billplz.redirect');

Route::get('/', function () {
    return view('welcome');
});
