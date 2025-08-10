<?php

declare(strict_types=1);

namespace Colame\Menu\Providers;

use Illuminate\Support\ServiceProvider;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Contracts\MenuSectionRepositoryInterface;
use Colame\Menu\Contracts\MenuItemRepositoryInterface;
use Colame\Menu\Contracts\MenuLocationRepositoryInterface;
use Colame\Menu\Contracts\MenuAvailabilityInterface;
use Colame\Menu\Contracts\MenuVersioningInterface;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Repositories\MenuRepository;
use Colame\Menu\Repositories\MenuSectionRepository;
use Colame\Menu\Repositories\MenuItemRepository;
use Colame\Menu\Repositories\MenuLocationRepository;
use Colame\Menu\Services\MenuAvailabilityService;
use Colame\Menu\Services\MenuVersioningService;
use Colame\Menu\Services\MenuService;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/features.php',
            'features'
        );
        
        // Bind repositories
        $this->app->bind(MenuRepositoryInterface::class, MenuRepository::class);
        $this->app->bind(MenuSectionRepositoryInterface::class, MenuSectionRepository::class);
        $this->app->bind(MenuItemRepositoryInterface::class, MenuItemRepository::class);
        $this->app->bind(MenuLocationRepositoryInterface::class, MenuLocationRepository::class);
        
        // Bind services
        $this->app->bind(MenuAvailabilityInterface::class, MenuAvailabilityService::class);
        $this->app->bind(MenuVersioningInterface::class, MenuVersioningService::class);
        $this->app->bind(MenuServiceInterface::class, MenuService::class);
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/menu-routes.php');
        
        // Load API routes
        $apiRoutesPath = __DIR__ . '/../../routes/api.php';
        if (file_exists($apiRoutesPath)) {
            $this->loadRoutesFrom($apiRoutesPath);
        }
        
        // Load web routes
        $webRoutesPath = __DIR__ . '/../../routes/web.php';
        if (file_exists($webRoutesPath)) {
            $this->loadRoutesFrom($webRoutesPath);
        }
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'menu');
        
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/features.php' => config_path('menu-features.php'),
        ], 'menu-config');
        
        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'menu-migrations');
    }
}
