<?php

use Colame\Order\Http\Controllers\Web\OrderController as WebOrderController;
use Colame\Order\Http\Controllers\Web\OrderFlowController as WebOrderFlowController;
use Colame\Order\Http\Controllers\Web\OrderSyncController as WebOrderSyncController;
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
        
        // Order Creation Flow
        Route::get('/new', [WebOrderController::class, 'new'])->name('new'); // Welcome screen
        Route::get('/session/{uuid}', [WebOrderController::class, 'session'])->name('session'); // Active session
        
        // Legacy create route (redirects to /new)
        Route::get('/create', function() {
            return redirect()->route('orders.new');
        })->name('create');
        Route::post('/', [WebOrderController::class, 'store'])->name('store');
        
        // Session-based order flow (comprehensive tracking)
        Route::prefix('session')->name('session.')->group(function () {
            // Start new session (rate limited to prevent abuse)
            Route::post('/start', [WebOrderFlowController::class, 'startSession'])
                ->name('start')
                ->middleware('throttle:10,1'); // 10 requests per minute per IP
            
            // Session operations
            Route::prefix('{orderUuid}')->group(function () {
                // SINGLE sync endpoint for ALL operations
                Route::post('/sync', [WebOrderSyncController::class, 'sync'])
                    ->name('sync')
                    ->middleware('throttle:60,1'); // 60 requests per minute
                
                // Session management
                Route::get('/state', [WebOrderFlowController::class, 'getSessionState'])->name('state');
                Route::post('/recover', [WebOrderFlowController::class, 'recoverSession'])->name('recover');
                Route::post('/save-draft', [WebOrderFlowController::class, 'saveDraft'])->name('save-draft');
                
                // Convert to order
                Route::post('/convert', [WebOrderFlowController::class, 'convertToOrder'])->name('convert');
            });
        });
        
        // NOTE: All specific routes must be defined BEFORE the dynamic {order} routes
        
        // Single Order Operations - with UUID constraint
        Route::get('/{order}', [WebOrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [WebOrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [WebOrderController::class, 'update'])->name('update');
        
        // Status Updates
        Route::post('/{order}/place', [WebOrderController::class, 'place'])->name('place');
        Route::post('/{order}/confirm', [WebOrderController::class, 'confirm'])->name('confirm');
        Route::post('/{order}/start-preparing', [WebOrderController::class, 'startPreparing'])->name('start-preparing');
        Route::post('/{order}/mark-ready', [WebOrderController::class, 'markReady'])->name('mark-ready');
        Route::post('/{order}/start-delivery', [WebOrderController::class, 'startDelivery'])->name('start-delivery');
        Route::post('/{order}/mark-delivered', [WebOrderController::class, 'markDelivered'])->name('mark-delivered');
        Route::post('/{order}/complete', [WebOrderController::class, 'complete'])->name('complete');
        
        // Cancellation
        Route::get('/{order}/cancel', [WebOrderController::class, 'showCancelForm'])->name('cancel.form');
        Route::post('/{order}/cancel', [WebOrderController::class, 'cancel'])->name('cancel');
        
        // Payment
        Route::get('/{order}/payment', [WebOrderController::class, 'payment'])->name('payment');
        Route::post('/{order}/payment/process', [WebOrderController::class, 'processPayment'])->name('payment.process');
        
        // Receipt
        Route::get('/{order}/receipt', [WebOrderController::class, 'receipt'])->name('receipt');
        
        // Event Sourcing - Time Travel and State Management
        Route::post('/{order}/events/state-at', [WebOrderController::class, 'getStateAtTimestamp'])->name('events.state-at');
        Route::post('/{order}/events/replay', [WebOrderController::class, 'replayEvents'])->name('events.replay');
        Route::post('/{order}/events/add', [WebOrderController::class, 'addEvent'])->name('events.add');
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
        Route::get('/{order}/next-states', [ApiOrderController::class, 'getNextStates'])->name('next-states');
        
        // Item management
        Route::patch('/{order}/items/{item}/status', [ApiOrderController::class, 'updateItemStatus'])->name('update-item-status');
        
        // Offers
        Route::post('/{order}/offers', [ApiOrderController::class, 'applyOffers'])->name('apply-offers');
        
        // Statistics
        Route::get('/statistics/summary', [ApiOrderController::class, 'statistics'])->name('statistics');
    });
});
