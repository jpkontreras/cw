<?php

use Illuminate\Support\Facades\Route;
use Colame\Item\Http\Controllers\Web\ItemController;
use Colame\Item\Http\Controllers\Web\ItemSearchController;
use Colame\Item\Http\Controllers\Web\ModifierController;
use Colame\Item\Http\Controllers\Web\InventoryController;
use Colame\Item\Http\Controllers\Web\PricingController;
use Colame\Item\Http\Controllers\Web\RecipeController;

Route::middleware(['web', 'auth', 'onboarding.completed'])->group(function () {
    // Item management routes
    Route::prefix('items')->name('item.')->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('index');
        Route::get('/create', [ItemController::class, 'create'])->name('create');
        Route::post('/', [ItemController::class, 'store'])->name('store');
        Route::post('/bulk-update', [ItemController::class, 'bulkUpdate'])->name('bulk-update');
        
        // Item search routes (for AJAX/JSON responses) - nested under items
        Route::prefix('search')->name('search.')->group(function () {
            Route::get('/', [ItemSearchController::class, 'search'])->name('query');
            Route::get('/suggestions', [ItemSearchController::class, 'suggestions'])->name('suggestions');
            Route::get('/popular', [ItemSearchController::class, 'popular'])->name('popular');
            Route::post('/select', [ItemSearchController::class, 'recordSelection'])->name('select');
            Route::get('/recent', [ItemSearchController::class, 'recentSearches'])->name('recent'); // Legacy - returns items now
            Route::delete('/recent', [ItemSearchController::class, 'clearRecentSearches'])->name('recent.clear');
            Route::get('/recent-items', [ItemSearchController::class, 'recentItems'])->name('recent.items');
            Route::delete('/recent-items', [ItemSearchController::class, 'clearRecentItems'])->name('recent.items.clear');
        });
        
        // Favorites management routes
        Route::prefix('favorites')->name('favorites.')->group(function () {
            Route::get('/', [ItemSearchController::class, 'favorites'])->name('index');
            Route::post('/toggle', [ItemSearchController::class, 'toggleFavorite'])->name('toggle');
        });
        
        Route::get('/{id}', [ItemController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ItemController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ItemController::class, 'update'])->name('update');
        Route::delete('/{id}', [ItemController::class, 'destroy'])->name('destroy');
    });
    
    // Modifier management routes
    Route::prefix('modifiers')->name('modifier.')->group(function () {
        Route::get('/', [ModifierController::class, 'index'])->name('index');
        Route::get('/create', [ModifierController::class, 'create'])->name('create');
        Route::get('/bulk-assign', [ModifierController::class, 'bulkAssign'])->name('bulk-assign');
        Route::post('/bulk-assign', [ModifierController::class, 'processBulkAssign'])->name('process-bulk-assign');
        Route::post('/', [ModifierController::class, 'store'])->name('store');
        Route::get('/{id}', [ModifierController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ModifierController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ModifierController::class, 'update'])->name('update');
        Route::delete('/{id}', [ModifierController::class, 'destroy'])->name('destroy');
        
        // Modifier items within groups
        Route::get('/{groupId}/modifiers/create', [ModifierController::class, 'createModifier'])->name('modifier.create');
        Route::post('/{groupId}/modifiers', [ModifierController::class, 'storeModifier'])->name('modifier.store');
        Route::put('/{groupId}/modifiers/{modifierId}', [ModifierController::class, 'updateModifier'])->name('modifier.update');
        Route::delete('/{groupId}/modifiers/{modifierId}', [ModifierController::class, 'destroyModifier'])->name('modifier.destroy');
        Route::post('/{groupId}/reorder', [ModifierController::class, 'reorderModifiers'])->name('reorder');
    });
    
    // Inventory management routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
        Route::get('/transfers', [InventoryController::class, 'transfers'])->name('transfers');
        Route::post('/transfer', [InventoryController::class, 'transfer'])->name('transfer');
        Route::get('/stock-take', [InventoryController::class, 'stockTake'])->name('stock-take');
        Route::post('/stock-take', [InventoryController::class, 'processStockTake'])->name('process-stock-take');
        Route::get('/reorder-settings', [InventoryController::class, 'reorderSettings'])->name('reorder-settings');
        Route::post('/reorder-levels', [InventoryController::class, 'updateReorderLevels'])->name('update-reorder-levels');
        Route::get('/history', [InventoryController::class, 'history'])->name('history');
        Route::get('/export', [InventoryController::class, 'export'])->name('export');
    });
    
    // Pricing management routes
    Route::prefix('pricing')->name('pricing.')->group(function () {
        Route::get('/', [PricingController::class, 'index'])->name('index');
        Route::get('/create', [PricingController::class, 'create'])->name('create');
        Route::post('/', [PricingController::class, 'store'])->name('store');
        Route::get('/{id}', [PricingController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PricingController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PricingController::class, 'update'])->name('update');
        Route::delete('/{id}', [PricingController::class, 'destroy'])->name('destroy');
        Route::get('/calculator', [PricingController::class, 'calculator'])->name('calculator');
        Route::post('/calculate', [PricingController::class, 'calculate'])->name('calculate');
        Route::get('/bulk-update', [PricingController::class, 'bulkUpdate'])->name('bulk-update');
        Route::post('/bulk-update', [PricingController::class, 'processBulkUpdate'])->name('process-bulk-update');
    });
    
    // Recipe management routes
    Route::prefix('recipes')->name('recipe.')->group(function () {
        Route::get('/', [RecipeController::class, 'index'])->name('index');
        Route::get('/create', [RecipeController::class, 'create'])->name('create');
        Route::post('/', [RecipeController::class, 'store'])->name('store');
        Route::get('/{id}', [RecipeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [RecipeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [RecipeController::class, 'update'])->name('update');
        Route::delete('/{id}', [RecipeController::class, 'destroy'])->name('destroy');
        Route::get('/production', [RecipeController::class, 'production'])->name('production');
        Route::post('/check-availability', [RecipeController::class, 'checkAvailability'])->name('check-availability');
        Route::post('/produce', [RecipeController::class, 'produce'])->name('produce');
        Route::get('/cost-analysis', [RecipeController::class, 'costAnalysis'])->name('cost-analysis');
    });
});