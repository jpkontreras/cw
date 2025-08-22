<?php

use Colame\Settings\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('system-settings')->name('api.system-settings.')->group(function () {
    // Get all settings grouped
    Route::get('/', [SettingController::class, 'index'])->name('index');
    
    // Get settings by category
    Route::get('/category/{category}', [SettingController::class, 'category'])->name('category');
    
    // Get specific setting value
    Route::get('/key/{key}', [SettingController::class, 'get'])->name('get');
    
    // Update specific setting
    Route::put('/key/{key}', [SettingController::class, 'update'])->name('update');
    
    // Bulk update
    Route::put('/bulk', [SettingController::class, 'bulkUpdate'])->name('bulk-update');
    
    // Category-specific endpoints
    Route::prefix('organization')->name('organization.')->group(function () {
        Route::get('/', [SettingController::class, 'organization'])->name('index');
        Route::put('/', [SettingController::class, 'updateOrganization'])->name('update');
    });
    
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [SettingController::class, 'orders'])->name('index');
        Route::put('/', [SettingController::class, 'updateOrders'])->name('update');
    });
    
    Route::prefix('receipts')->name('receipts.')->group(function () {
        Route::get('/', [SettingController::class, 'receipts'])->name('index');
        Route::put('/', [SettingController::class, 'updateReceipts'])->name('update');
    });
    
    Route::prefix('tax')->name('tax.')->group(function () {
        Route::get('/', [SettingController::class, 'tax'])->name('index');
        Route::put('/', [SettingController::class, 'updateTax'])->name('update');
    });
    
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [SettingController::class, 'notifications'])->name('index');
        Route::put('/', [SettingController::class, 'updateNotifications'])->name('update');
    });
    
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [SettingController::class, 'integrations'])->name('index');
        Route::put('/', [SettingController::class, 'updateIntegrations'])->name('update');
    });
    
    // Validation
    Route::post('/validate', [SettingController::class, 'validateSettings'])->name('validate');
    
    // Import/Export
    Route::get('/export', [SettingController::class, 'export'])->name('export');
    Route::post('/import', [SettingController::class, 'import'])->name('import');
    
    // Reset operations
    Route::post('/reset-category', [SettingController::class, 'resetCategory'])->name('reset-category');
    Route::post('/reset-all', [SettingController::class, 'resetAll'])->name('reset-all');
    
    // Initialize defaults
    Route::post('/initialize', [SettingController::class, 'initialize'])->name('initialize');
    
    // Missing required settings
    Route::get('/missing-required', [SettingController::class, 'missingRequired'])->name('missing-required');
});