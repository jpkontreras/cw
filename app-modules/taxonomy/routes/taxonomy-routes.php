<?php

use Colame\Taxonomy\Http\Controllers\Api\TaxonomyController as ApiTaxonomyController;
use Colame\Taxonomy\Http\Controllers\Web\TaxonomyController as WebTaxonomyController;
use Illuminate\Support\Facades\Route;

// Web Routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('taxonomies')->name('taxonomies.')->group(function () {
        Route::get('/', [WebTaxonomyController::class, 'index'])->name('index');
        Route::get('/create', [WebTaxonomyController::class, 'create'])->name('create');
        Route::post('/', [WebTaxonomyController::class, 'store'])->name('store');
        Route::get('/tree', [WebTaxonomyController::class, 'tree'])->name('tree');
        Route::get('/{id}', [WebTaxonomyController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [WebTaxonomyController::class, 'edit'])->name('edit');
        Route::put('/{id}', [WebTaxonomyController::class, 'update'])->name('update');
        Route::delete('/{id}', [WebTaxonomyController::class, 'destroy'])->name('destroy');
    });
});

// API Routes
Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(function () {
    Route::prefix('taxonomies')->name('api.taxonomies.')->group(function () {
        Route::get('/', [ApiTaxonomyController::class, 'index'])->name('index');
        Route::post('/', [ApiTaxonomyController::class, 'store'])->name('store');
        Route::post('/bulk', [ApiTaxonomyController::class, 'bulkCreate'])->name('bulk-create');
        Route::get('/search', [ApiTaxonomyController::class, 'search'])->name('search');
        Route::get('/popular', [ApiTaxonomyController::class, 'popular'])->name('popular');
        Route::get('/tree', [ApiTaxonomyController::class, 'tree'])->name('tree');
        Route::get('/{id}', [ApiTaxonomyController::class, 'show'])->name('show');
        Route::put('/{id}', [ApiTaxonomyController::class, 'update'])->name('update');
        Route::delete('/{id}', [ApiTaxonomyController::class, 'destroy'])->name('destroy');
    });
});
