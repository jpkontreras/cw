<?php

use Illuminate\Support\Facades\Route;
use Colame\Offer\Http\Controllers\Web\OfferController as WebOfferController;
use Colame\Offer\Http\Controllers\Api\OfferController as ApiOfferController;

// Web Routes (Inertia)
Route::middleware(['web', 'auth'])->prefix('offers')->name('offers.')->group(function () {
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
    Route::get('/{offer}/analytics', [WebOfferController::class, 'analytics'])->name('analytics');
    Route::post('/bulk-action', [WebOfferController::class, 'bulkAction'])->name('bulk-action');
});

// API Routes
Route::middleware(['api', 'auth:sanctum'])->prefix('api/offers')->name('api.offers.')->group(function () {
    Route::get('/', [ApiOfferController::class, 'index'])->name('index');
    Route::post('/', [ApiOfferController::class, 'store'])->name('store');
    Route::get('/{offer}', [ApiOfferController::class, 'show'])->name('show');
    Route::put('/{offer}', [ApiOfferController::class, 'update'])->name('update');
    Route::delete('/{offer}', [ApiOfferController::class, 'destroy'])->name('destroy');
    
    // Additional API actions
    Route::post('/{offer}/duplicate', [ApiOfferController::class, 'duplicate'])->name('duplicate');
    Route::post('/{offer}/activate', [ApiOfferController::class, 'activate'])->name('activate');
    Route::post('/{offer}/deactivate', [ApiOfferController::class, 'deactivate'])->name('deactivate');
    Route::get('/{offer}/analytics', [ApiOfferController::class, 'analytics'])->name('analytics');
    Route::post('/bulk-action', [ApiOfferController::class, 'bulkAction'])->name('bulk-action');
    
    // Order-related endpoints
    Route::post('/validate', [ApiOfferController::class, 'validateOffer'])->name('validate');
    Route::post('/apply', [ApiOfferController::class, 'apply'])->name('apply');
    Route::post('/apply-best', [ApiOfferController::class, 'applyBest'])->name('apply-best');
    Route::post('/available', [ApiOfferController::class, 'available'])->name('available');
    Route::post('/check-code', [ApiOfferController::class, 'checkCode'])->name('check-code');
});
