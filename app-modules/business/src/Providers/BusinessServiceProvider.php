<?php

declare(strict_types=1);

namespace Colame\Business\Providers;

use Colame\Business\Contracts\BusinessContextInterface;
use Colame\Business\Contracts\BusinessRepositoryInterface;
use Colame\Business\Contracts\BusinessServiceInterface;
use Colame\Business\Contracts\BusinessUserRepositoryInterface;
use Colame\Business\Http\Middleware\EnsureBusinessContext;
use Colame\Business\Models\Business;
use Colame\Business\Repositories\BusinessRepository;
use Colame\Business\Repositories\BusinessUserRepository;
use Colame\Business\Services\BusinessContextService;
use Colame\Business\Services\BusinessService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BusinessServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(BusinessRepositoryInterface::class, BusinessRepository::class);
        $this->app->bind(BusinessUserRepositoryInterface::class, BusinessUserRepository::class);

        // Register service bindings
        $this->app->bind(BusinessServiceInterface::class, BusinessService::class);

        // Register business context as singleton
        $this->app->singleton(BusinessContextInterface::class, BusinessContextService::class);

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

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/business-routes.php');

        // Load views (for blade templates if needed)
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'business');


        // Register route model bindings
        Route::model('business', Business::class);

        // Register middleware
        $this->app['router']->aliasMiddleware('business.context', EnsureBusinessContext::class);

        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/features.php' => config_path('features/business.php'),
            ], 'business-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'business-migrations');
        }

        // Share business context with all views (for Inertia)
        if (class_exists(\Inertia\Inertia::class)) {
            \Inertia\Inertia::share([
                'businessContext' => function () {
                    if (!auth()->check()) {
                        return null;
                    }

                    $context = app(BusinessContextInterface::class);
                    $current = $context->getCurrentBusiness();

                    return $current ? [
                        'current' => $current->toArray(),
                        'role' => $context->getCurrentRole(),
                        'permissions' => [
                            'canManageUsers' => $context->can('manage_users'),
                            'canManageSettings' => $context->can('manage_settings'),
                            'canManageLocations' => $context->can('manage_locations'),
                        ],
                    ] : null;
                },
                'accessibleBusinesses' => function () {
                    if (!auth()->check()) {
                        return [];
                    }

                    $context = app(BusinessContextInterface::class);
                    return $context->getAccessibleBusinesses();
                },
            ]);
        }
    }
}
