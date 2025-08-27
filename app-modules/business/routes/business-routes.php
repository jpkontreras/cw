<?php

use Colame\Business\Http\Controllers\Web\BusinessController as WebBusinessController;
use Colame\Business\Http\Controllers\Api\BusinessController as ApiBusinessController;
use Illuminate\Support\Facades\Route;

// Web Routes (Inertia)
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::prefix('businesses')->name('businesses.')->group(function () {
        Route::get('/', [WebBusinessController::class, 'index'])->name('index');
        Route::get('/current', [WebBusinessController::class, 'current'])->name('current');
        Route::get('/create', [WebBusinessController::class, 'create'])->name('create');
        Route::post('/', [WebBusinessController::class, 'store'])->name('store');
        Route::get('/settings', [WebBusinessController::class, 'currentSettings'])->name('settings');
        Route::get('/users', [WebBusinessController::class, 'currentUsers'])->name('users');
        
        Route::prefix('{business}')->group(function () {
            Route::get('/', [WebBusinessController::class, 'show'])->name('show');
            Route::get('/edit', [WebBusinessController::class, 'edit'])->name('edit');
            Route::put('/', [WebBusinessController::class, 'update'])->name('update');
            Route::delete('/', [WebBusinessController::class, 'destroy'])->name('destroy');
            
            Route::post('/switch', [WebBusinessController::class, 'switch'])->name('switch');
            Route::get('/settings', [WebBusinessController::class, 'settings'])->name('business.settings');
            Route::post('/settings/branding', [WebBusinessController::class, 'updateBranding'])->name('business.branding');
            Route::post('/settings/features', [WebBusinessController::class, 'updateFeatures'])->name('business.features');
            Route::post('/settings/notifications', [WebBusinessController::class, 'updateNotifications'])->name('business.notifications');
            
            // User management
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [WebBusinessController::class, 'users'])->name('index');
                Route::post('/invite', [WebBusinessController::class, 'inviteUser'])->name('invite');
                Route::delete('/{user}', [WebBusinessController::class, 'removeUser'])->name('remove');
                Route::patch('/{user}/role', [WebBusinessController::class, 'updateUserRole'])->name('update-role');
            });
        });
    });
});

// API Routes
Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1')->group(function () {
    Route::prefix('businesses')->name('api.businesses.')->group(function () {
        Route::get('/', [ApiBusinessController::class, 'index'])->name('index');
        Route::post('/', [ApiBusinessController::class, 'store'])->name('store');
        Route::get('/current', [ApiBusinessController::class, 'current'])->name('current');
        Route::post('/accept-invitation', [ApiBusinessController::class, 'acceptInvitation'])->name('accept-invitation');
        
        Route::prefix('{business}')->group(function () {
            Route::get('/', [ApiBusinessController::class, 'show'])->name('show');
            Route::put('/', [ApiBusinessController::class, 'update'])->name('update');
            Route::delete('/', [ApiBusinessController::class, 'destroy'])->name('destroy');
            
            Route::post('/switch', [ApiBusinessController::class, 'switch'])->name('switch');
            Route::get('/metrics', [ApiBusinessController::class, 'metrics'])->name('metrics');
            
            // User management
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [ApiBusinessController::class, 'users'])->name('index');
                Route::post('/invite', [ApiBusinessController::class, 'inviteUser'])->name('invite');
                Route::delete('/{user}', [ApiBusinessController::class, 'removeUser'])->name('remove');
                Route::patch('/{user}/role', [ApiBusinessController::class, 'updateUserRole'])->name('update-role');
            });
        });
    });
});