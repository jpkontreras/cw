<?php

namespace Colame\Location\Providers;

use Illuminate\Support\ServiceProvider;
use Colame\Location\Contracts\LocationRepositoryInterface;
use Colame\Location\Contracts\LocationServiceInterface;
use Colame\Location\Repositories\LocationRepository;
use Colame\Location\Services\LocationService;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        
        // Register service bindings
        $this->app->bind(LocationServiceInterface::class, LocationService::class);
        
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/features.php',
            'features'
        );
        
        // Merge location-specific config if exists
        if (file_exists(__DIR__ . '/../../config/location.php')) {
            $this->mergeConfigFrom(
                __DIR__ . '/../../config/location.php',
                'location'
            );
        }
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the Inertia share provider
        $this->app->register(LocationInertiaShareProvider::class);
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Load routes
        if (file_exists(__DIR__ . '/../../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        }
        
        if (file_exists(__DIR__ . '/../../routes/api.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        }
        
        // Load legacy route file if exists
        if (file_exists(__DIR__ . '/../../routes/location-routes.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/location-routes.php');
        }
        
        // Register event listeners
        $this->registerEventListeners();
        
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/features.php' => config_path('features/location.php'),
            ], 'location-config');
            
            // Publish migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'location-migrations');
        }
    }
    
    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // Register location event listeners here when we create them
    }
}
