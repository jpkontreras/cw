<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Protected routes that require authentication, verification, and completed onboarding
Route::middleware(['auth', 'verified', 'onboarding.completed'])->group(function () {
    // Routes that require business context
    Route::middleware(['business.context'])->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('dashboard');
        })->name('dashboard');
        
        // All other application routes that need business context
        // Examples:
        // Route::resource('orders', OrderController::class);
        // Route::resource('items', ItemController::class);
        // Route::resource('staff', StaffController::class);
    });
    
    // Routes that don't require business context are handled in module routes
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
