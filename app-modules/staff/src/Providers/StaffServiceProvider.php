<?php

namespace Colame\Staff\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Colame\Staff\Contracts\StaffRepositoryInterface;
use Colame\Staff\Contracts\RoleRepositoryInterface;
use Colame\Staff\Contracts\ShiftRepositoryInterface;
use Colame\Staff\Contracts\AttendanceRepositoryInterface;
use Colame\Staff\Contracts\StaffServiceInterface;
use Colame\Staff\Contracts\ShiftServiceInterface;
use Colame\Staff\Contracts\AttendanceServiceInterface;

class StaffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(
            \Colame\Staff\Contracts\StaffRepositoryInterface::class,
            \Colame\Staff\Repositories\StaffRepository::class
        );
        $this->app->bind(
            \Colame\Staff\Contracts\RoleRepositoryInterface::class,
            \Colame\Staff\Repositories\RoleRepository::class
        );
        $this->app->bind(
            \Colame\Staff\Contracts\ShiftRepositoryInterface::class,
            \Colame\Staff\Repositories\ShiftRepository::class
        );
        $this->app->bind(
            \Colame\Staff\Contracts\AttendanceRepositoryInterface::class,
            \Colame\Staff\Repositories\AttendanceRepository::class
        );
    }
    
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->bootRoutes();

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'staff');
        
        // Load and merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/features.php',
            'features'
        );

        // Override spatie's Role model with our extended version
        $this->app->config->set('permission.models.role', \Colame\Staff\Models\Role::class);
    }

    private function bootRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/staff-routes.php');
    }
}