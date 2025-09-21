<?php

namespace Colame\AiDiscovery\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Colame\AiDiscovery\Contracts\AiDiscoveryInterface;
use Colame\AiDiscovery\Contracts\FoodIntelligenceInterface;
use Colame\AiDiscovery\Contracts\SimilarityCacheInterface;
use Colame\AiDiscovery\Services\AiDiscoveryService;
use Colame\AiDiscovery\Services\FoodIntelligenceService;
use Colame\AiDiscovery\Services\SimilarityCacheService;
use Colame\AiDiscovery\Services\PromptEngineeringService;
use Colame\AiDiscovery\Services\AiResponseCacheService;

class AiDiscoveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(AiDiscoveryInterface::class, AiDiscoveryService::class);
        $this->app->bind(FoodIntelligenceInterface::class, FoodIntelligenceService::class);
        $this->app->bind(SimilarityCacheInterface::class, SimilarityCacheService::class);

        // Register PromptEngineeringService as singleton
        $this->app->singleton(PromptEngineeringService::class, function ($app) {
            return new PromptEngineeringService();
        });

        // Register AiResponseCacheService as singleton
        $this->app->singleton(AiResponseCacheService::class, function ($app) {
            return new AiResponseCacheService();
        });

        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ai-discovery.php',
            'ai-discovery'
        );
    }

    public function boot(): void
    {
        $this->bootRoutes();

        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/ai-discovery.php' => config_path('ai-discovery.php'),
            ], 'ai-discovery-config');
        }
    }

    private function bootRoutes(): void
    {
        // API Routes
        Route::prefix('api/ai-discovery')
            ->middleware(['api'])
            ->namespace('Colame\AiDiscovery\Http\Controllers\Api')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
            });

        // Web Routes (if needed for admin interface)
        Route::middleware('web')
            ->namespace('Colame\AiDiscovery\Http\Controllers')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
            });
    }
}