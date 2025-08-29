<?php

use Illuminate\Support\Facades\Route;
use Colame\Order\Http\Controllers\Api\OrderFlowController;

Route::prefix('orders/flow')->group(function () {
    // Start new order
    Route::post('/start', [OrderFlowController::class, 'startOrder']);
    
    // Order operations
    Route::prefix('{orderUuid}')->group(function () {
        // Add/modify items
        Route::post('/items', [OrderFlowController::class, 'addItems']);
        
        // Apply/remove promotions
        Route::post('/promotion', [OrderFlowController::class, 'applyPromotion']);
        
        // Add tip
        Route::post('/tip', [OrderFlowController::class, 'addTip']);
        
        // Confirm order
        Route::post('/confirm', [OrderFlowController::class, 'confirmOrder']);
        
        // Cancel order
        Route::post('/cancel', [OrderFlowController::class, 'cancelOrder']);
        
        // Get current state (for polling/recovery)
        Route::get('/state', [OrderFlowController::class, 'getOrderState']);
    });
});