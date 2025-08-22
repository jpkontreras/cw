<?php

namespace Colame\Settings\Providers;

use Colame\Settings\Contracts\SettingCacheInterface;
use Colame\Settings\Contracts\SettingRepositoryInterface;
use Colame\Settings\Contracts\SettingServiceInterface;
use Colame\Settings\Repositories\SettingRepository;
use Colame\Settings\Services\SettingCacheService;
use Colame\Settings\Services\SettingService;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind as singleton for better performance
        $this->app->singleton(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->singleton(SettingServiceInterface::class, SettingService::class);

        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/features.php',
            'features.settings'
        );
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/settings-routes.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/features.php' => config_path('features/settings.php'),
        ], 'settings-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'settings-migrations');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Colame\Settings\Console\Commands\InitializeSettingsCommand::class,
            ]);
        }
    }
}
