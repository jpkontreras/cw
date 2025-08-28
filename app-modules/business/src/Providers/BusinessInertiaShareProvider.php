<?php

declare(strict_types=1);

namespace Colame\Business\Providers;

use Colame\Business\Contracts\BusinessContextInterface;
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
                // Use BusinessContextService to get business data
                $businessContext = app(BusinessContextInterface::class);
                
                // Get user's effective business (current or first available)
                $currentBusiness = $businessContext->getEffectiveBusiness($user->id);
                
                // Get all businesses accessible by the user
                // We need to get businesses directly using the repository since Auth::user() might not be set in this context
                $businessRepository = app(\Colame\Business\Contracts\BusinessRepositoryInterface::class);
                $businessesCollection = $businessRepository->getUserBusinesses($user->id);
                $businesses = $businessesCollection->toArray();
                
                return [
                    'current' => $currentBusiness?->toArray(),
                    'businesses' => $businesses,
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