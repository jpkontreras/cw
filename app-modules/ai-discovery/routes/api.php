<?php

use Illuminate\Support\Facades\Route;
use Colame\AiDiscovery\Http\Controllers\Api\AiDiscoveryController;

/*
|--------------------------------------------------------------------------
| AI Discovery API Routes
|--------------------------------------------------------------------------
|
| Routes for AI-powered food discovery system
|
*/

Route::controller(AiDiscoveryController::class)->group(function () {
    // Session management
    Route::post('/sessions/start', 'startSession')->name('ai.discovery.start');
    Route::post('/sessions/{sessionId}/process', 'processResponse')->name('ai.discovery.process');
    Route::post('/sessions/{sessionId}/stream', 'streamResponse')->name('ai.discovery.stream');
    Route::post('/sessions/{sessionId}/complete', 'completeSession')->name('ai.discovery.complete');
    Route::get('/sessions/{sessionId}/context', 'getContext')->name('ai.discovery.context');
    Route::post('/sessions/{sessionId}/resume', 'resumeSession')->name('ai.discovery.resume');
    Route::post('/sessions/{sessionId}/abandon', 'abandonSession')->name('ai.discovery.abandon');
    Route::get('/sessions', 'getUserSessions')->name('ai.discovery.sessions');

    // AI Analysis
    Route::post('/analyze', 'analyzeItem')->name('ai.discovery.analyze');
    Route::post('/allergens', 'identifyAllergens')->name('ai.discovery.allergens');
    Route::post('/pricing', 'getPricingSuggestions')->name('ai.discovery.pricing');

    // Cache and similarity
    Route::post('/search-similar', 'searchSimilar')->name('ai.discovery.similar');
    Route::get('/cache-stats', 'getCacheStats')->name('ai.discovery.cache.stats');

    // Learning and feedback
    Route::post('/feedback', 'submitFeedback')->name('ai.discovery.feedback');
});