<?php

use Illuminate\Support\Facades\Route;
use Colame\Item\Http\Controllers\Api\ItemController;
use Colame\Item\Http\Controllers\Api\ModifierController;
use Colame\Item\Http\Controllers\Api\InventoryController;
use Colame\Item\Http\Controllers\Api\RecipeController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Item endpoints
    Route::prefix('items')->group(function () {
        Route::get('/', [ItemController::class, 'index']);
        Route::post('/', [ItemController::class, 'store']);
        Route::get('/{id}', [ItemController::class, 'show']);
        Route::put('/{id}', [ItemController::class, 'update']);
        Route::patch('/{id}', [ItemController::class, 'update']);
        Route::delete('/{id}', [ItemController::class, 'destroy']);
        Route::post('/search', [ItemController::class, 'search']);
        Route::get('/{id}/availability', [ItemController::class, 'checkAvailability']);
        Route::get('/{id}/price', [ItemController::class, 'calculatePrice']);
        Route::post('/bulk', [ItemController::class, 'bulk']);
        
        // Item modifier groups
        Route::get('/{itemId}/modifiers', [ModifierController::class, 'getItemModifierGroups']);
    });
    
    // Modifier endpoints
    Route::prefix('modifiers')->group(function () {
        Route::get('/groups', [ModifierController::class, 'index']);
        Route::post('/groups', [ModifierController::class, 'store']);
        Route::get('/groups/{id}', [ModifierController::class, 'show']);
        Route::put('/groups/{id}', [ModifierController::class, 'update']);
        Route::delete('/groups/{id}', [ModifierController::class, 'destroy']);
        Route::post('/validate', [ModifierController::class, 'validateSelections']);
        
        // Modifiers within groups
        Route::post('/groups/{groupId}/modifiers', [ModifierController::class, 'storeModifier']);
        Route::put('/groups/{groupId}/modifiers/{modifierId}', [ModifierController::class, 'updateModifier']);
        Route::delete('/groups/{groupId}/modifiers/{modifierId}', [ModifierController::class, 'destroyModifier']);
    });
    
    // Inventory endpoints
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/items/{itemId}', [InventoryController::class, 'show']);
        Route::post('/adjust', [InventoryController::class, 'adjust']);
        Route::post('/transfer', [InventoryController::class, 'transfer']);
        Route::post('/reserve', [InventoryController::class, 'reserve']);
        Route::post('/release', [InventoryController::class, 'release']);
        Route::get('/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('/reorder-list', [InventoryController::class, 'reorderList']);
        Route::put('/reorder-levels', [InventoryController::class, 'updateReorderLevels']);
        Route::get('/history', [InventoryController::class, 'history']);
        Route::post('/stock-take', [InventoryController::class, 'stockTake']);
    });
    
    // Recipe endpoints
    Route::prefix('recipes')->group(function () {
        Route::get('/', [RecipeController::class, 'index']);
        Route::post('/', [RecipeController::class, 'store']);
        Route::get('/{id}', [RecipeController::class, 'show']);
        Route::put('/{id}', [RecipeController::class, 'update']);
        Route::delete('/{id}', [RecipeController::class, 'destroy']);
        Route::get('/items/{itemId}', [RecipeController::class, 'getByItem']);
        Route::get('/{id}/cost', [RecipeController::class, 'calculateCost']);
        Route::post('/check-availability', [RecipeController::class, 'checkAvailability']);
        Route::post('/produce', [RecipeController::class, 'produce']);
        Route::get('/ingredients', [RecipeController::class, 'ingredients']);
        Route::get('/ingredients/{ingredientItemId}/recipes', [RecipeController::class, 'recipesUsingIngredient']);
    });
});