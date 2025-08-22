<?php

use Illuminate\Support\Facades\Route;
use Colame\Onboarding\Http\Controllers\Api\OnboardingController;

Route::middleware(['auth:sanctum'])->prefix('api/onboarding')->name('api.onboarding.')->group(function () {
    Route::get('/progress', [OnboardingController::class, 'getProgress'])->name('progress');
    
    // Process individual steps
    Route::post('/account', [OnboardingController::class, 'processAccount'])->name('account');
    Route::post('/business', [OnboardingController::class, 'processBusiness'])->name('business');
    Route::post('/location', [OnboardingController::class, 'processLocation'])->name('location');
    Route::post('/configuration', [OnboardingController::class, 'processConfiguration'])->name('configuration');
    
    // Complete, skip, or reset
    Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    Route::post('/skip', [OnboardingController::class, 'skip'])->name('skip');
    Route::post('/reset', [OnboardingController::class, 'reset'])->name('reset');
});