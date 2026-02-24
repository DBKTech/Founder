<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\OrderWorkflowController;
use App\Http\Middleware\ResolveTenantContext;


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

Route::get('/', function () {
    return view('welcome');
});
