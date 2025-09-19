<?php

use Colame\Order\Http\Controllers\Api\OrderSlipController;
use Colame\Order\Http\Controllers\Api\OrderSessionController;
use Colame\Order\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// API Routes for Orders Module
// These routes are loaded under /api/v1/orders prefix
Route::middleware(['auth:sanctum'])->group(function () {
    // Order Session Management
    Route::post('/session/start', [OrderSessionController::class, 'start'])->name('session.start');
    Route::post('/session/{uuid}/sync', [OrderSessionController::class, 'sync'])->name('session.sync');
    Route::post('/session/{uuid}/add-item', [OrderSessionController::class, 'addItem'])->name('session.add-item');
    Route::delete('/session/{uuid}/items/{itemIndex}', [OrderSessionController::class, 'removeItem'])->name('session.remove-item');
    Route::post('/session/{uuid}/checkout', [OrderSessionController::class, 'checkout'])->name('session.checkout');

    // Order Management
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{orderId}', [OrderController::class, 'show'])->name('show');
    Route::post('/{orderId}/confirm', [OrderController::class, 'confirm'])->name('confirm');
    Route::post('/{orderId}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    Route::post('/{orderId}/status', [OrderController::class, 'changeStatus'])->name('change-status');
    Route::get('/{orderId}/state-at-timestamp', [OrderController::class, 'getStateAtTimestamp'])->name('state-at-timestamp');

    // Order Slip (barcode scanning)
    Route::post('/slip/scan', [OrderSlipController::class, 'scan'])->name('slip.scan');
    Route::post('/{orderId}/slip/print', [OrderSlipController::class, 'print'])->name('slip.print');
    Route::get('/slip/print-queue', [OrderSlipController::class, 'getPrintQueue'])->name('slip.print-queue');
});