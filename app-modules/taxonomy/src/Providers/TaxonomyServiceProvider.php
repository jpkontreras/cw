<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Providers;

use Colame\Taxonomy\Contracts\TaxonomyRepositoryInterface;
use Colame\Taxonomy\Contracts\TaxonomyServiceInterface;
use Colame\Taxonomy\Repositories\TaxonomyRepository;
use Colame\Taxonomy\Services\TaxonomyService;
use Illuminate\Support\ServiceProvider;

class TaxonomyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repository binding
        $this->app->bind(TaxonomyRepositoryInterface::class, TaxonomyRepository::class);
        
        // Register service binding
        $this->app->bind(TaxonomyServiceInterface::class, TaxonomyService::class);
        
        // Register config
        $this->mergeConfigFrom(__DIR__ . '/../../config/features.php', 'taxonomy.features');
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/taxonomy-routes.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'taxonomy');
        
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/features.php' => config_path('taxonomy/features.php'),
            ], 'taxonomy-config');
        }
    }
}
