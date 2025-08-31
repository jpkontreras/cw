<?php

use Colame\Order\Http\Controllers\Web\OrderController as WebOrderController;
use Colame\Order\Http\Controllers\Api\OrderController as ApiOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Module Routes
|--------------------------------------------------------------------------
|
| Here are all routes for the Order module, separated by Web and API
|
*/

// Web Routes (Inertia)
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::prefix('orders')->name('orders.')->group(function () {
        // List and Dashboard
        Route::get('/', [WebOrderController::class, 'index'])->name('index');
        Route::get('/dashboard', [WebOrderController::class, 'dashboard'])->name('dashboard');
        Route::get('/operations', [WebOrderController::class, 'operations'])->name('operations');
        Route::get('/kitchen', [WebOrderController::class, 'kitchen'])->name('kitchen');
        
        // Create
        Route::get('/create', [WebOrderController::class, 'create'])->name('create');
        Route::get('/create-v2', [WebOrderController::class, 'createV2'])->name('create-v2');
        Route::post('/', [WebOrderController::class, 'store'])->name('store');
        
        // NOTE: All specific routes must be defined BEFORE the dynamic {order} routes
        
        // Single Order Operations - with numeric constraint to prevent conflicts
        Route::get('/{order}', [WebOrderController::class, 'show'])->name('show')->where('order', '[0-9]+');
        Route::get('/{order}/edit', [WebOrderController::class, 'edit'])->name('edit')->where('order', '[0-9]+');
        Route::put('/{order}', [WebOrderController::class, 'update'])->name('update')->where('order', '[0-9]+');
        
        // Status Updates
        Route::post('/{order}/place', [WebOrderController::class, 'place'])->name('place')->where('order', '[0-9]+');
        Route::post('/{order}/confirm', [WebOrderController::class, 'confirm'])->name('confirm')->where('order', '[0-9]+');
        Route::post('/{order}/start-preparing', [WebOrderController::class, 'startPreparing'])->name('start-preparing')->where('order', '[0-9]+');
        Route::post('/{order}/mark-ready', [WebOrderController::class, 'markReady'])->name('mark-ready')->where('order', '[0-9]+');
        Route::post('/{order}/start-delivery', [WebOrderController::class, 'startDelivery'])->name('start-delivery')->where('order', '[0-9]+');
        Route::post('/{order}/mark-delivered', [WebOrderController::class, 'markDelivered'])->name('mark-delivered')->where('order', '[0-9]+');
        Route::post('/{order}/complete', [WebOrderController::class, 'complete'])->name('complete')->where('order', '[0-9]+');
        
        // Cancellation
        Route::get('/{order}/cancel', [WebOrderController::class, 'showCancelForm'])->name('cancel.form')->where('order', '[0-9]+');
        Route::post('/{order}/cancel', [WebOrderController::class, 'cancel'])->name('cancel')->where('order', '[0-9]+');
        
        // Payment
        Route::get('/{order}/payment', [WebOrderController::class, 'payment'])->name('payment')->where('order', '[0-9]+');
        Route::post('/{order}/payment/process', [WebOrderController::class, 'processPayment'])->name('payment.process')->where('order', '[0-9]+');
        
        // Receipt
        Route::get('/{order}/receipt', [WebOrderController::class, 'receipt'])->name('receipt')->where('order', '[0-9]+');
    });
});

// API Routes
Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1')->group(function () {
    Route::prefix('orders')->name('api.orders.')->group(function () {
        // Basic CRUD
        Route::get('/', [ApiOrderController::class, 'index'])->name('index');
        Route::post('/', [ApiOrderController::class, 'store'])->name('store');
        Route::get('/{order}', [ApiOrderController::class, 'show'])->name('show');
        Route::put('/{order}', [ApiOrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [ApiOrderController::class, 'destroy'])->name('destroy');
        
        // Status management
        Route::patch('/{order}/status', [ApiOrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{order}/cancel', [ApiOrderController::class, 'cancel'])->name('cancel');
        
        // Item management
        Route::patch('/{order}/items/{item}/status', [ApiOrderController::class, 'updateItemStatus'])->name('update-item-status');
        
        // Offers
        Route::post('/{order}/offers', [ApiOrderController::class, 'applyOffers'])->name('apply-offers');
        
        // Statistics
        Route::get('/statistics/summary', [ApiOrderController::class, 'statistics'])->name('statistics');
    });
});
