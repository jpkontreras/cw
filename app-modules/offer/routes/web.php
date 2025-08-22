<?php

use Illuminate\Support\Facades\Route;
use Colame\Offer\Http\Controllers\Web\OfferController as WebOfferController;

// Web Routes (Inertia)
Route::middleware(['web', 'auth', 'onboarding.completed'])->prefix('offers')->name('offers.')->group(function () {
    Route::get('/', [WebOfferController::class, 'index'])->name('index');
    Route::get('/create', [WebOfferController::class, 'create'])->name('create');
    Route::post('/', [WebOfferController::class, 'store'])->name('store');
    Route::get('/{offer}', [WebOfferController::class, 'show'])->name('show');
    Route::get('/{offer}/edit', [WebOfferController::class, 'edit'])->name('edit');
    Route::put('/{offer}', [WebOfferController::class, 'update'])->name('update');
    Route::delete('/{offer}', [WebOfferController::class, 'destroy'])->name('destroy');
    
    // Additional actions
    Route::post('/{offer}/duplicate', [WebOfferController::class, 'duplicate'])->name('duplicate');
    Route::post('/{offer}/activate', [WebOfferController::class, 'activate'])->name('activate');
    Route::post('/{offer}/deactivate', [WebOfferController::class, 'deactivate'])->name('deactivate');
    Route::get('/analytics', [WebOfferController::class, 'analytics'])->name('analytics');
    Route::post('/bulk-action', [WebOfferController::class, 'bulkAction'])->name('bulk-action');
});