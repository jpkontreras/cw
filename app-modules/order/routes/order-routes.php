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
        Route::post('/', [WebOrderController::class, 'store'])->name('store');
        
        // Single Order Operations
        Route::get('/{id}', [WebOrderController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [WebOrderController::class, 'edit'])->name('edit');
        Route::put('/{id}', [WebOrderController::class, 'update'])->name('update');
        
        // Status Updates
        Route::post('/{id}/place', [WebOrderController::class, 'store'])->name('place');
        Route::post('/{id}/confirm', [WebOrderController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/start-preparing', [WebOrderController::class, 'startPreparing'])->name('start-preparing');
        Route::post('/{id}/mark-ready', [WebOrderController::class, 'markReady'])->name('mark-ready');
        Route::post('/{id}/start-delivery', [WebOrderController::class, 'startDelivery'])->name('start-delivery');
        Route::post('/{id}/mark-delivered', [WebOrderController::class, 'markDelivered'])->name('mark-delivered');
        Route::post('/{id}/complete', [WebOrderController::class, 'complete'])->name('complete');
        
        // Cancellation
        Route::get('/{id}/cancel', [WebOrderController::class, 'showCancelForm'])->name('cancel.form');
        Route::post('/{id}/cancel', [WebOrderController::class, 'cancel'])->name('cancel');
        
        // Payment
        Route::get('/{id}/payment', [WebOrderController::class, 'payment'])->name('payment');
        Route::post('/{id}/payment/process', [WebOrderController::class, 'processPayment'])->name('payment.process');
        
        // Receipt
        Route::get('/{id}/receipt', [WebOrderController::class, 'receipt'])->name('receipt');
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
