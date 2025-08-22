<?php

use Illuminate\Support\Facades\Route;
use Colame\Onboarding\Http\Controllers\Web\OnboardingController;

Route::middleware(['web', 'auth'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'index'])->name('index');
    
    // Step routes
    Route::get('/account', [OnboardingController::class, 'accountSetup'])->name('account');
    Route::post('/account', [OnboardingController::class, 'storeAccountSetup']);
    
    Route::get('/business', [OnboardingController::class, 'businessSetup'])->name('business');
    Route::post('/business', [OnboardingController::class, 'storeBusinessSetup']);
    
    Route::get('/location', [OnboardingController::class, 'locationSetup'])->name('location');
    Route::post('/location', [OnboardingController::class, 'storeLocationSetup']);
    
    Route::get('/configuration', [OnboardingController::class, 'configurationSetup'])->name('configuration');
    Route::post('/configuration', [OnboardingController::class, 'storeConfigurationSetup']);
    
    // Review and complete
    Route::get('/review', [OnboardingController::class, 'review'])->name('review');
    Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    Route::post('/skip', [OnboardingController::class, 'skip'])->name('skip');
});