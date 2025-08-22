<?php

use Illuminate\Support\Facades\Route;
use Colame\Location\Http\Controllers\Web\LocationController;

Route::middleware(['web', 'auth', 'onboarding.completed'])->group(function () {
    // Static routes MUST be defined before resource routes to avoid route conflicts
    // Location filtering and viewing routes
    Route::get('locations/types', [LocationController::class, 'types'])->name('locations.types');
    Route::get('locations/hierarchy', [LocationController::class, 'hierarchy'])->name('locations.hierarchy');
    Route::get('locations/settings', [LocationController::class, 'generalSettings'])->name('locations.settings.general');
    
    // Resource routes (includes dynamic {location} parameter)
    Route::resource('locations', LocationController::class);
    
    // Additional location management routes (these use the {location} parameter)
    Route::get('locations/{location}/users', [LocationController::class, 'users'])->name('locations.users');
    Route::get('locations/{location}/settings', [LocationController::class, 'settings'])->name('locations.settings');
    Route::put('locations/{location}/settings', [LocationController::class, 'updateSettings'])->name('locations.settings.update');
});