<?php

use Illuminate\Support\Facades\Route;
use Colame\Menu\Http\Controllers\Web\MenuController;
use Colame\Menu\Http\Controllers\Web\MenuSectionController;
use Colame\Menu\Http\Controllers\Web\MenuItemController;
use Colame\Menu\Http\Controllers\Web\MenuBuilderController;

Route::middleware(['auth', 'web'])->prefix('menus')->name('menus.')->group(function () {
    // Menu Management
    Route::get('/', [MenuController::class, 'index'])->name('index');
    Route::get('/create', [MenuController::class, 'create'])->name('create');
    Route::post('/', [MenuController::class, 'store'])->name('store');
    Route::get('/{menu}', [MenuController::class, 'show'])->name('show');
    Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
    Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
    Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
    
    // Menu Actions
    Route::post('/{menu}/activate', [MenuController::class, 'activate'])->name('activate');
    Route::post('/{menu}/deactivate', [MenuController::class, 'deactivate'])->name('deactivate');
    Route::post('/{menu}/duplicate', [MenuController::class, 'duplicate'])->name('duplicate');
    Route::post('/{menu}/set-default', [MenuController::class, 'setDefault'])->name('set-default');
    
    // Menu Builder
    Route::get('/{menu}/builder', [MenuBuilderController::class, 'index'])->name('builder');
    Route::post('/{menu}/builder/save', [MenuBuilderController::class, 'save'])->name('builder.save');
    
    // Menu Sections
    Route::prefix('{menu}/sections')->name('sections.')->group(function () {
        Route::get('/', [MenuSectionController::class, 'index'])->name('index');
        Route::post('/', [MenuSectionController::class, 'store'])->name('store');
        Route::put('/{section}', [MenuSectionController::class, 'update'])->name('update');
        Route::delete('/{section}', [MenuSectionController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [MenuSectionController::class, 'reorder'])->name('reorder');
    });
    
    // Menu Items
    Route::prefix('{menu}/items')->name('items.')->group(function () {
        Route::get('/', [MenuItemController::class, 'index'])->name('index');
        Route::post('/', [MenuItemController::class, 'store'])->name('store');
        Route::put('/{item}', [MenuItemController::class, 'update'])->name('update');
        Route::delete('/{item}', [MenuItemController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [MenuItemController::class, 'reorder'])->name('reorder');
        Route::post('/{item}/toggle-featured', [MenuItemController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::post('/{item}/toggle-availability', [MenuItemController::class, 'toggleAvailability'])->name('toggle-availability');
    });
    
    // Menu Preview
    Route::get('/{menu}/preview', [MenuController::class, 'preview'])->name('preview');
    
    // Menu Export
    Route::get('/{menu}/export/{format}', [MenuController::class, 'export'])->name('export');
});