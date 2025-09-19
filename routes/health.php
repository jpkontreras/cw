<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| These routes are used by Docker and load balancers to check the health
| of the application. They should be lightweight and fast.
|
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'server' => config('octane.server'),
    ]);
});

Route::get('/health/deep', function () {
    $checks = [
        'app' => true,
        'database' => false,
        'redis' => false,
        'storage' => false,
    ];

    // Check database connection
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        // Database not available
    }

    // Check Redis connection
    try {
        Redis::ping();
        $checks['redis'] = true;
    } catch (\Exception $e) {
        // Redis not available
    }

    // Check storage write permissions
    try {
        $testFile = storage_path('app/.health-check');
        file_put_contents($testFile, 'test');
        unlink($testFile);
        $checks['storage'] = true;
    } catch (\Exception $e) {
        // Storage not writable
    }

    $healthy = !in_array(false, $checks);

    return response()->json([
        'status' => $healthy ? 'healthy' : 'degraded',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});