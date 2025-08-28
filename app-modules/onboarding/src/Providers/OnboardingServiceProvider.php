<?php

namespace Colame\Onboarding\Providers;

use Colame\Onboarding\Contracts\OnboardingRepositoryInterface;
use Colame\Onboarding\Contracts\OnboardingServiceInterface;
use Colame\Onboarding\Repositories\OnboardingRepository;
use Colame\Onboarding\Services\OnboardingService;
use Illuminate\Support\ServiceProvider;

class OnboardingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(OnboardingRepositoryInterface::class, OnboardingRepository::class);
        $this->app->bind(OnboardingServiceInterface::class, OnboardingService::class);
    }

    public function boot(): void
    {
        $modulePath = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($modulePath . '/database/migrations');
        $this->loadRoutesFrom($modulePath . '/routes/web.php');
        $this->loadRoutesFrom($modulePath . '/routes/api.php');
        $this->loadViewsFrom($modulePath . '/resources/views', 'onboarding');

        // Register feature config if it exists
        $configPath = $modulePath . '/config/features.php';
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'features.onboarding');
        }

        // Middleware is now registered in bootstrap/app.php as 'onboarding.completed'
        // No need to register here to avoid conflicts
    }
}
