<?php

namespace Colame\Item\Providers;

use App\Core\Services\UnifiedSearchService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Contracts\ItemSearchInterface;
use Colame\Item\Contracts\ModifierRepositoryInterface;
use Colame\Item\Contracts\PricingRepositoryInterface;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use Colame\Item\Contracts\RecipeRepositoryInterface;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Repositories\ItemRepository;
use Colame\Item\Repositories\ModifierRepository;
use Colame\Item\Repositories\PricingRepository;
use Colame\Item\Repositories\InventoryRepository;
use Colame\Item\Repositories\RecipeRepository;
use Colame\Item\Services\ItemSearchService;
use Colame\Item\Services\ItemService;

class ItemServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);
        $this->app->bind(ModifierRepositoryInterface::class, ModifierRepository::class);
        $this->app->bind(PricingRepositoryInterface::class, PricingRepository::class);
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(RecipeRepositoryInterface::class, RecipeRepository::class);
        
        // Register service bindings
        $this->app->bind(ItemServiceInterface::class, ItemService::class);
        $this->app->bind(ItemSearchInterface::class, ItemSearchService::class);
        
        // Register as singleton for better performance
        $this->app->singleton(ItemSearchService::class);
        
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/features.php',
            'features'
        );
        
        // Merge item-specific config if exists
        if (file_exists(__DIR__ . '/../../config/item.php')) {
            $this->mergeConfigFrom(
                __DIR__ . '/../../config/item.php',
                'item'
            );
        }
    }
    
    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Load routes
        if (file_exists(__DIR__ . '/../../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        }

        if (file_exists(__DIR__ . '/../../routes/api.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        }
        
        // Register event listeners
        $this->registerEventListeners();
        
        // Register search module with UnifiedSearchService
        if ($this->app->bound(UnifiedSearchService::class)) {
            $this->app->make(UnifiedSearchService::class)->registerModule(
                'items',
                $this->app->make(ItemSearchService::class)
            );
        }
        
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/features.php' => config_path('features/item.php'),
            ], 'item-config');
            
            // Publish migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'item-migrations');
            
            // Register commands
            $this->commands([
                \Colame\Item\Console\Commands\GenerateItemsCommand::class,
                \Colame\Item\Console\Commands\SetupModifiersCommand::class,
            ]);
        }
    }
    
    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        // Register item event listeners here when we create them
    }
}