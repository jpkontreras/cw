<?php

declare(strict_types=1);

namespace Colame\Business\Providers;

use App\Services\UserBusinessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

/**
 * Provides business data sharing for Inertia requests.
 * This keeps business logic within the business module boundaries.
 */
class BusinessInertiaShareProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share business data with all Inertia responses
        Inertia::share('business', function () {
            $request = request();
            $user = $request->user();
            
            if (!$user) {
                return [
                    'current' => null,
                    'businesses' => [],
                ];
            }

            try {
                // Use UserBusinessService to get business data
                $userBusinessService = app(UserBusinessService::class);
                
                // Get user's current business
                $currentBusiness = $userBusinessService->getCurrentBusiness($user);
                
                // Get all businesses accessible by the user
                $businesses = $userBusinessService->getUserBusinesses($user);
                
                return [
                    'current' => $currentBusiness?->toArray(),
                    'businesses' => $businesses->toArray(),
                ];
            } catch (\Exception $e) {
                Log::debug('Business data error in BusinessInertiaShareProvider: ' . $e->getMessage());
                
                return [
                    'current' => null,
                    'businesses' => [],
                ];
            }
        });
    }
}