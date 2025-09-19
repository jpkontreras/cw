<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DefaultImagesController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API v1 routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Authentication routes (public)
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user'])->name('user');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->name('refresh');
        Route::post('/revoke-all', [AuthController::class, 'revokeAllTokens'])->name('revoke-all');
    });
});

// Search routes
Route::prefix('search')->name('search.')->group(function () {
    Route::get('/', [SearchController::class, 'global'])->name('global');
    Route::get('/suggest', [SearchController::class, 'suggest'])->name('suggest');
    Route::get('/popular', [SearchController::class, 'popular'])->name('popular');
    Route::post('/select', [SearchController::class, 'recordSelection'])->name('select');
    Route::get('/{type}', [SearchController::class, 'searchType'])->name('type');
});

// Default images
Route::get('/default-images', [DefaultImagesController::class, 'index'])->name('default-images');

// Other protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Add other v1 routes here as modules are versioned
});

// Module routes - versioned under v1
// These routes are loaded from module route files
// Each module maintains its own route file for better organization

// Order module routes
Route::prefix('orders')->name('orders.')->group(function () {
    require base_path('app-modules/order/routes/api.php');
});

// Item module routes
Route::prefix('items')->name('items.')->group(function () {
    require base_path('app-modules/item/routes/api.php');
});

// Menu module routes
Route::prefix('menu')->name('menu.')->group(function () {
    require base_path('app-modules/menu/routes/api.php');
});

// Location module routes
Route::prefix('locations')->name('locations.')->group(function () {
    require base_path('app-modules/location/routes/api.php');
});

// Settings module routes
Route::prefix('settings')->name('settings.')->group(function () {
    require base_path('app-modules/settings/routes/api.php');
});

// Offer module routes
Route::prefix('offers')->name('offers.')->group(function () {
    require base_path('app-modules/offer/routes/api.php');
});

// Onboarding module routes
Route::prefix('onboarding')->name('onboarding.')->group(function () {
    require base_path('app-modules/onboarding/routes/api.php');
});