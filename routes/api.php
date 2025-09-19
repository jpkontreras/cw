<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
| API Versioning Strategy:
| - All routes are versioned under /api/v1
| - v1 routes are loaded from routes/api/v1.php
| - New features should be added to versioned routes only
|
*/

// Load versioned API routes
Route::prefix('v1')->name('v1.')->group(function () {
    require __DIR__.'/api/v1.php';
});
