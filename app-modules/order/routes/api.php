<?php

use Illuminate\Support\Facades\Route;
use Colame\Order\Http\Controllers\Api\OrderFlowController;

// Session-based order flow (new comprehensive tracking)
Route::prefix('orders/session')->group(function () {
    // Start new session (rate limited to prevent abuse)
    Route::post('/start', [OrderFlowController::class, 'initiateSession'])
        ->middleware('throttle:10,1'); // 10 requests per minute per IP
    
    // Session operations
    Route::prefix('{orderUuid}')->group(function () {
        // Cart operations
        Route::post('/cart/add', [OrderFlowController::class, 'addToCart']);
        Route::post('/cart/remove', [OrderFlowController::class, 'removeFromCart']);
        Route::post('/cart/update', [OrderFlowController::class, 'updateCartItem']);
        
        // Session management
        Route::get('/state', [OrderFlowController::class, 'getSessionState']);
        Route::post('/recover', [OrderFlowController::class, 'recoverSession']);
        Route::post('/save-draft', [OrderFlowController::class, 'saveDraft']);
        
        // Convert to order
        Route::post('/convert', [OrderFlowController::class, 'convertToOrder']);
    });
});

// Legacy flow (kept for backward compatibility)
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