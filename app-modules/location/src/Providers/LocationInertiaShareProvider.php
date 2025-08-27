<?php

declare(strict_types=1);

namespace Colame\Location\Providers;

use Colame\Location\Contracts\LocationServiceInterface;
use Colame\Location\Services\UserLocationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

/**
 * Provides location data sharing for Inertia requests.
 * This keeps location logic within the location module boundaries.
 */
class LocationInertiaShareProvider extends ServiceProvider
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
        // Share location data with all Inertia responses
        Inertia::share('location', function () {
            $request = request();
            $user = $request->user();
            
            return $this->getLocationData($user);
        });
    }

    /**
     * Get location data for the authenticated user.
     */
    private function getLocationData($user): array
    {
        if (!$user) {
            return [
                'current' => null,
                'locations' => [],
            ];
        }

        try {
            // Use UserLocationService to maintain module boundaries
            $userLocationService = app(UserLocationService::class);
            $locationService = app(LocationServiceInterface::class);
            
            // Get user's effective location using the service
            $currentLocation = null;
            $effectiveLocation = $userLocationService->getEffectiveLocation($user);
            if ($effectiveLocation) {
                $currentLocation = $effectiveLocation->toArray();
            }
            
            // Get all locations accessible by the user
            $locations = $userLocationService->getUserLocations($user);
            
            // If user has no locations but there's a default location, assign them to it
            if ($locations->count() === 0) {
                try {
                    // Try to find a default location using the repository interface
                    $defaultLocation = $locationService->getDefaultLocation();
                    if ($defaultLocation) {
                        // Assign user to default location using the service
                        $userLocationService->attachUserToLocation($user, $defaultLocation->id, 'staff', true);
                        // Re-fetch locations
                        $locations = $userLocationService->getUserLocations($user);
                        if (!$currentLocation) {
                            $currentLocation = $defaultLocation->toArray();
                        }
                    }
                } catch (\Exception $e) {
                    // No default location available, continue with empty locations
                    Log::debug('No default location available: ' . $e->getMessage());
                }
            }
            
            return [
                'current' => $currentLocation,
                'locations' => $locations->toArray(),
            ];
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::debug('Location data error in LocationInertiaShareProvider: ' . $e->getMessage());
            // If location module is not available or there's an error, return empty data
            return [
                'current' => null,
                'locations' => [],
            ];
        }
    }
}