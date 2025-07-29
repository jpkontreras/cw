<?php

use Illuminate\Support\Facades\Route;

// Item module routes will be defined here
// Web routes
Route::middleware(['web', 'auth'])->prefix('items')->name('items.')->group(function () {
    // Routes will be added as we create controllers
});

// API routes
Route::middleware(['api', 'auth:sanctum'])->prefix('api/items')->name('api.items.')->group(function () {
    // API routes will be added as we create controllers
});