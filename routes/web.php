<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Protected routes that require authentication, verification, and completed onboarding
Route::middleware(['auth', 'verified', 'onboarding.completed'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    
    // All other application routes that need onboarding should go here
    // Examples:
    // Route::resource('orders', OrderController::class);
    // Route::resource('items', ItemController::class);
    // Route::resource('staff', StaffController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
