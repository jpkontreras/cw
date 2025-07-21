<?php

namespace Colame\Order\Providers;

use Colame\Order\Console\Commands\GenerateSampleOrdersCommand;
use Colame\Order\Contracts\OrderItemRepositoryInterface;
use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Models\Order;
use Colame\Order\Repositories\OrderItemRepository;
use Colame\Order\Repositories\OrderRepository;
use Colame\Order\Services\OrderService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderItemRepositoryInterface::class, OrderItemRepository::class);
        
        // Register service bindings
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/features.php',
            'features'
        );
    }
    
    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register route model bindings
        Route::model('order', Order::class);
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/order-routes.php');
        
        // Register event listeners
        $this->registerEventListeners();
        
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/features.php' => config_path('features/order.php'),
            ], 'order-config');
            
            // Register commands
            $this->commands([
                GenerateSampleOrdersCommand::class,
            ]);
        }
    }
    
    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        // Register order event listeners here when we create them
        // For example:
        // Event::listen(OrderCreated::class, [NotifyKitchen::class, 'handle']);
    }
}
