<?php

use Illuminate\Support\Facades\Route;
use Colame\Menu\Http\Controllers\Api\MenuController;
use Colame\Menu\Http\Controllers\Api\MenuSectionController;
use Colame\Menu\Http\Controllers\Api\MenuItemController;
use Colame\Menu\Http\Controllers\Api\MenuAvailabilityController;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/menus')->name('api.menus.')->group(function () {
    // Menu endpoints
    Route::get('/', [MenuController::class, 'index'])->name('index');
    Route::get('/active', [MenuController::class, 'active'])->name('active');
    Route::get('/available', [MenuController::class, 'available'])->name('available');
    Route::get('/location/{locationId}', [MenuController::class, 'byLocation'])->name('by-location');
    Route::get('/{menu}', [MenuController::class, 'show'])->name('show');
    Route::get('/{menu}/structure', [MenuController::class, 'structure'])->name('structure');
    
    // Menu management (admin only)
    Route::middleware(['can:manage-menus'])->group(function () {
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
        Route::post('/{menu}/activate', [MenuController::class, 'activate'])->name('activate');
        Route::post('/{menu}/deactivate', [MenuController::class, 'deactivate'])->name('deactivate');
    });
    
    // Menu sections
    Route::prefix('{menu}/sections')->name('sections.')->group(function () {
        Route::get('/', [MenuSectionController::class, 'index'])->name('index');
        Route::get('/{section}', [MenuSectionController::class, 'show'])->name('show');
        Route::get('/{section}/items', [MenuSectionController::class, 'items'])->name('items');
        
        Route::middleware(['can:manage-menus'])->group(function () {
            Route::post('/', [MenuSectionController::class, 'store'])->name('store');
            Route::put('/{section}', [MenuSectionController::class, 'update'])->name('update');
            Route::delete('/{section}', [MenuSectionController::class, 'destroy'])->name('destroy');
        });
    });
    
    // Menu items
    Route::prefix('{menu}/items')->name('items.')->group(function () {
        Route::get('/', [MenuItemController::class, 'index'])->name('index');
        Route::get('/featured', [MenuItemController::class, 'featured'])->name('featured');
        Route::get('/{item}', [MenuItemController::class, 'show'])->name('show');
        
        Route::middleware(['can:manage-menus'])->group(function () {
            Route::post('/', [MenuItemController::class, 'store'])->name('store');
            Route::put('/{item}', [MenuItemController::class, 'update'])->name('update');
            Route::delete('/{item}', [MenuItemController::class, 'destroy'])->name('destroy');
        });
    });
    
    // Menu availability
    Route::prefix('availability')->name('availability.')->group(function () {
        Route::get('/check/{menu}', [MenuAvailabilityController::class, 'check'])->name('check');
        Route::get('/schedule/{menu}', [MenuAvailabilityController::class, 'schedule'])->name('schedule');
        Route::get('/current', [MenuAvailabilityController::class, 'current'])->name('current');
        Route::get('/location/{locationId}', [MenuAvailabilityController::class, 'byLocation'])->name('by-location');
    });
});

// Public API endpoints (no auth required)
Route::prefix('api/public/menus')->name('api.public.menus.')->group(function () {
    Route::get('/default', [MenuController::class, 'getDefault'])->name('default');
    Route::get('/location/{locationId}', [MenuController::class, 'publicByLocation'])->name('by-location');
    Route::get('/{slug}', [MenuController::class, 'publicShow'])->name('show');
    Route::get('/{slug}/structure', [MenuController::class, 'publicStructure'])->name('structure');
});