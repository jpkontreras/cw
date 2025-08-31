<?php

use App\Http\Controllers\Api\DefaultImagesController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/default-images', [DefaultImagesController::class, 'index'])->name('api.default-images');

// Search routes
Route::prefix('search')->name('api.search.')->group(function () {
    Route::get('/', [SearchController::class, 'global'])->name('global');
    Route::get('/suggest', [SearchController::class, 'suggest'])->name('suggest');
    Route::get('/popular', [SearchController::class, 'popular'])->name('popular');
    Route::post('/select', [SearchController::class, 'recordSelection'])->name('select');
    Route::get('/{type}', [SearchController::class, 'searchType'])->name('type');
});
