<?php

namespace App\Http\Middleware;

use Colame\Location\Contracts\LocationServiceInterface;
use Colame\Location\Data\LocationData;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $locationData = $this->getLocationData($user);
        
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'ziggy' => fn(): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'location' => $locationData,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
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
            $locationService = app(LocationServiceInterface::class);
            
            // Get user's current location
            $currentLocation = null;
            $effectiveLocation = $user->getEffectiveLocation();
            if ($effectiveLocation) {
                $currentLocation = LocationData::fromModel($effectiveLocation)->toArray();
            }
            
            // Get all locations accessible by the user
            $locations = $locationService->getUserAccessibleLocations($user->id);
            
            // If user has no locations but there's a default location, assign them to it
            if ($locations->count() === 0) {
                // Try to find a default location
                $defaultLocation = \Colame\Location\Models\Location::where('is_default', true)->first();
                if ($defaultLocation) {
                    // Assign user to default location
                    $locationService->assignUserToLocation($user->id, $defaultLocation->id, 'staff', true);
                    // Re-fetch locations
                    $locations = $locationService->getUserAccessibleLocations($user->id);
                    if (!$currentLocation) {
                        $currentLocation = LocationData::fromModel($defaultLocation)->toArray();
                    }
                }
            }
            
            return [
                'current' => $currentLocation,
                'locations' => $locations->toArray(),
            ];
        } catch (\Exception $e) {
            // Log the error for debugging
            // If location module is not available or there's an error, return empty data
            return [
                'current' => null,
                'locations' => [],
            ];
        }
    }
}
