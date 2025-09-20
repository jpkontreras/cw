<?php

use Colame\Order\Http\Controllers\Web\OrderController;
use Illuminate\Support\Facades\Route;

// Web Routes - Pure Event Sourced Order Module
Route::middleware(['web', 'auth', 'verified'])->prefix('orders')->name('orders.')->group(function () {

    // Order Flow - Web Views
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/new', [OrderController::class, 'new'])->name('new'); // Step 1: Welcome/type selection
    Route::post('/start', [OrderController::class, 'start'])->name('start'); // Start session after type selection

    // Operations Center (must be before dynamic orderId route)
    Route::get('/operations', [OrderController::class, 'operations'])->name('operations');

    // Kitchen View
    Route::get('/kitchen-display', [OrderController::class, 'kitchen'])->name('kitchen');

    // Session and order detail routes (dynamic params last)
    Route::get('/session/{uuid}', [OrderController::class, 'session'])->name('session'); // Step 2: Item picker
    Route::get('/{orderId}', [OrderController::class, 'show'])->name('show'); // Step 4: Order detail

    // API-like endpoints that return Inertia responses (for form submissions)
    Route::post('/session/start', [OrderController::class, 'startSession'])->name('session.start'); // AJAX session start
    Route::post('/session/{uuid}/sync', [OrderController::class, 'syncSession'])->name('session.sync'); // Sync session data
    Route::post('/session/{uuid}/add-item', [OrderController::class, 'addItem'])->name('add-item');
    Route::delete('/session/{uuid}/items/{itemIndex}', [OrderController::class, 'removeItem'])->name('remove-item');
    Route::post('/session/{uuid}/checkout', [OrderController::class, 'checkout'])->name('checkout'); // Step 3: Convert to order
    Route::post('/{orderId}/confirm', [OrderController::class, 'confirm'])->name('confirm');
    Route::post('/{orderId}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    Route::post('/{orderId}/status', [OrderController::class, 'changeStatus'])->name('change-status');
    Route::post('/{orderId}/kitchen-status', [OrderController::class, 'updateKitchenStatus'])->name('kitchen-status');
});
