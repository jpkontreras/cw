<?php

namespace App\Providers;

use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Services\FeatureFlagService;
use App\Services\UserBusinessService;
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
        
        // Bridge services
        $this->app->singleton(UserBusinessService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
