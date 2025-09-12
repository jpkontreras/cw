<?php

namespace Colame\Order\Providers;

use App\Core\Services\UnifiedSearchService;
use Colame\Order\Console\Commands\GenerateSampleOrdersCommand;
use Colame\Order\Console\Commands\RebuildOrderSessionsCommand;
use Colame\Order\Contracts\OrderItemRepositoryInterface;
use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Contracts\OrderSearchInterface;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Models\Order;
use Colame\Order\Repositories\OrderItemRepository;
use Colame\Order\Repositories\OrderRepository;
use Colame\Order\Services\OrderSearchService;
use Colame\Order\Services\OrderService;
use Colame\Order\Services\EventSourcedOrderService;
use Colame\Order\Services\OrderCalculationService;
use Colame\Order\Services\OrderValidationService;
use Colame\Order\Services\OrderSessionService;
use Colame\Order\Projectors\OrderProjector;
use Colame\Order\Projectors\OrderSessionProjector;
use Colame\Order\Projectors\OrderFromSessionProjector;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\EventSourcing\Facades\Projectionist;

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
        $this->app->bind(OrderSearchInterface::class, OrderSearchService::class);
        
        // Register event-sourced services
        // EventSourcedOrderService will auto-resolve LocationRepositoryInterface if available
        $this->app->singleton(EventSourcedOrderService::class);
        $this->app->singleton(OrderCalculationService::class);
        $this->app->singleton(OrderValidationService::class);
        $this->app->singleton(OrderSessionService::class);
        
        // Register as singleton for better performance
        $this->app->singleton(OrderSearchService::class);
        
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
        
        // Register event sourcing projectors
        Projectionist::addProjector(OrderProjector::class);
        Projectionist::addProjector(OrderSessionProjector::class);
        Projectionist::addProjector(OrderFromSessionProjector::class);
        
        // Register event listeners
        $this->registerEventListeners();
        
        // Register search module with UnifiedSearchService
        if ($this->app->bound(UnifiedSearchService::class)) {
            $this->app->make(UnifiedSearchService::class)->registerModule(
                'orders',
                $this->app->make(OrderSearchService::class)
            );
        }
        
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/features.php' => config_path('features/order.php'),
            ], 'order-config');
            
            // Register commands
            $this->commands([
                GenerateSampleOrdersCommand::class,
                RebuildOrderSessionsCommand::class,
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
