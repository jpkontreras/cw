<?php

use Illuminate\Support\Facades\Route;
use Colame\Item\Http\Controllers\Web\ItemController as WebItemController;
use Colame\Item\Http\Controllers\Api\ItemController as ApiItemController;

// Web routes
Route::middleware(['web', 'auth'])
    ->prefix('items')
    ->name('items.')
    ->group(function() {
        Route::get('/', [WebItemController::class, 'index'])->name('index');
        Route::get('/create', [WebItemController::class, 'create'])->name('create');
        Route::post('/', [WebItemController::class, 'store'])->name('store');
        Route::get('/search', [WebItemController::class, 'search'])->name('search');
        Route::get('/{id}', [WebItemController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [WebItemController::class, 'edit'])->name('edit');
        Route::put('/{id}', [WebItemController::class, 'update'])->name('update');
        Route::delete('/{id}', [WebItemController::class, 'destroy'])->name('destroy');
    });

// API routes
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/v1/items')
    ->name('api.items.')
    ->group(function() {
        Route::get('/', [ApiItemController::class, 'index'])->name('index');
        Route::post('/', [ApiItemController::class, 'store'])->name('store');
        Route::get('/search', [ApiItemController::class, 'search'])->name('search');
        Route::get('/{id}', [ApiItemController::class, 'show'])->name('show');
        Route::put('/{id}', [ApiItemController::class, 'update'])->name('update');
        Route::delete('/{id}', [ApiItemController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/availability', [ApiItemController::class, 'checkAvailability'])->name('availability');
        Route::get('/{id}/price', [ApiItemController::class, 'getPrice'])->name('price');
    });
