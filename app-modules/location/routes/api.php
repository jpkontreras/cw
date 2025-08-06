<?php

use Illuminate\Support\Facades\Route;
use Colame\Location\Http\Controllers\Api\LocationController;

Route::middleware(['api'])->prefix('api')->group(function () {
    
    // Public location endpoints
    Route::get('locations', [LocationController::class, 'index']);
    Route::get('locations/{location}', [LocationController::class, 'show']);
    Route::get('locations/{location}/check-open', [LocationController::class, 'checkOpen']);
    Route::post('locations/validate-code', [LocationController::class, 'validateCode']);
    Route::get('locations/by-capability/{capability}', [LocationController::class, 'byCapability']);
    
    // Authenticated location endpoints
    Route::middleware(['auth:sanctum'])->group(function () {
        // CRUD operations
        Route::post('locations', [LocationController::class, 'store']);
        Route::put('locations/{location}', [LocationController::class, 'update']);
        Route::delete('locations/{location}', [LocationController::class, 'destroy']);
        
        // User location management
        Route::get('locations/current', [LocationController::class, 'current']);
        Route::post('locations/current', [LocationController::class, 'setCurrent']);
    });
});