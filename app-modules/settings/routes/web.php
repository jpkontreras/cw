<?php

use Colame\Settings\Http\Controllers\Web\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('system-settings')->name('system-settings.')->group(function () {
    // Main settings dashboard
    Route::get('/', [SettingController::class, 'index'])->name('index');

    // Category-specific settings pages
    Route::get('/organization', [SettingController::class, 'organization'])->name('organization');
    Route::put('/organization', [SettingController::class, 'updateOrganization'])->name('organization.update');

    Route::get('/orders', [SettingController::class, 'orders'])->name('orders');
    Route::put('/orders', [SettingController::class, 'updateOrders'])->name('orders.update');

    Route::get('/receipts', [SettingController::class, 'receipts'])->name('receipts');
    Route::put('/receipts', [SettingController::class, 'updateReceipts'])->name('receipts.update');

    Route::get('/tax', [SettingController::class, 'tax'])->name('tax');
    Route::put('/tax', [SettingController::class, 'updateTax'])->name('tax.update');

    Route::get('/notifications', [SettingController::class, 'notifications'])->name('notifications');
    Route::put('/notifications', [SettingController::class, 'updateNotifications'])->name('notifications.update');

    Route::get('/integrations', [SettingController::class, 'integrations'])->name('integrations');
    Route::put('/integrations', [SettingController::class, 'updateIntegrations'])->name('integrations.update');

    // Bulk operations
    Route::put('/bulk', [SettingController::class, 'bulkUpdate'])->name('bulk-update');

    // Import/Export
    Route::get('/export', [SettingController::class, 'export'])->name('export');
    Route::get('/import', [SettingController::class, 'importForm'])->name('import.form');
    Route::post('/import', [SettingController::class, 'import'])->name('import');

    // Reset operations
    Route::post('/reset-category', [SettingController::class, 'resetCategory'])->name('reset-category');

    // Initialize defaults
    Route::post('/initialize', [SettingController::class, 'initialize'])->name('initialize');
});
