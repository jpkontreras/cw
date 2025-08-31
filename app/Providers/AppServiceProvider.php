<?php

namespace App\Providers;

use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Services\FeatureFlagService;
use App\Core\Services\UnifiedSearchService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Core service bindings
        $this->app->singleton(FeatureFlagInterface::class, FeatureFlagService::class);
        
        // Register UnifiedSearchService as singleton for module registration
        $this->app->singleton(UnifiedSearchService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
