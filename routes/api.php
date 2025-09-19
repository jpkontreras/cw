<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DefaultImagesController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
});

Route::get('/default-images', [DefaultImagesController::class, 'index'])->name('api.default-images');

// Search routes
Route::prefix('search')->name('api.search.')->group(function () {
    Route::get('/', [SearchController::class, 'global'])->name('global');
    Route::get('/suggest', [SearchController::class, 'suggest'])->name('suggest');
    Route::get('/popular', [SearchController::class, 'popular'])->name('popular');
    Route::post('/select', [SearchController::class, 'recordSelection'])->name('select');
    Route::get('/{type}', [SearchController::class, 'searchType'])->name('type');
});
