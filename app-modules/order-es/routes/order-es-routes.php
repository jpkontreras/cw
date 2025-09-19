<?php

use Colame\OrderEs\Http\Controllers\Web\OrderController;
use Colame\OrderEs\Http\Controllers\Api\OrderSlipController;
use Illuminate\Support\Facades\Route;

// Web Routes - Pure Event Sourced Order Module
Route::middleware(['web', 'auth', 'verified'])->prefix('es-order')->name('es-order.')->group(function () {
    
    // Order Flow
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/new', [OrderController::class, 'new'])->name('new'); // Step 1: Welcome/type selection
    Route::post('/start', [OrderController::class, 'start'])->name('start'); // Start session after type selection
    Route::post('/session/start', [OrderController::class, 'startSession'])->name('session.start'); // AJAX session start
    Route::get('/session/{uuid}', [OrderController::class, 'session'])->name('session'); // Step 2: Item picker
    Route::post('/session/{uuid}/sync', [OrderController::class, 'syncSession'])->name('session.sync'); // Sync session data
    Route::post('/session/{uuid}/add-item', [OrderController::class, 'addItem'])->name('add-item');
    Route::delete('/session/{uuid}/items/{itemIndex}', [OrderController::class, 'removeItem'])->name('remove-item');
    Route::post('/session/{uuid}/checkout', [OrderController::class, 'checkout'])->name('checkout'); // Step 3: Convert to order
    Route::get('/{orderId}', [OrderController::class, 'show'])->name('show'); // Step 4: Order detail
    
    // Order Management
    Route::post('/{orderId}/confirm', [OrderController::class, 'confirm'])->name('confirm');
    Route::post('/{orderId}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    Route::post('/{orderId}/status', [OrderController::class, 'changeStatus'])->name('change-status');

    // Time Travel API
    Route::get('/{orderId}/state-at-timestamp', [OrderController::class, 'getStateAtTimestamp'])->name('state-at-timestamp');
    
    // Kitchen View
    Route::get('/kitchen/display', [OrderController::class, 'kitchen'])->name('kitchen');
});

// API Routes for Order Slip (barcode scanning)
Route::middleware(['api', 'auth:sanctum'])->prefix('api/es-order')->name('api.es-order.')->group(function () {
    Route::post('/slip/scan', [OrderSlipController::class, 'scan'])->name('slip.scan');
    Route::post('/{orderId}/slip/print', [OrderSlipController::class, 'print'])->name('slip.print');
    Route::get('/slip/print-queue', [OrderSlipController::class, 'getPrintQueue'])->name('slip.print-queue');
});
