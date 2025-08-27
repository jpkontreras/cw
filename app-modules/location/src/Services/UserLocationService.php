<?php

declare(strict_types=1);

namespace Colame\Location\Services;

use App\Models\User;
use Colame\Location\Contracts\LocationRepositoryInterface;
use Colame\Location\Data\LocationData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service to handle user-location relationships
 * Manages the bridge between the core User model and the Location module
 */
class UserLocationService
{
    public function __construct(
        private ?LocationRepositoryInterface $locationRepository = null
    ) {}

    /**
     * Get all locations accessible by a user
     */
    public function getUserLocations(User $user): Collection
    {
        if (!$this->locationRepository) {
            return collect();
        }

        $locationIds = DB::table('location_user')
            ->where('user_id', $user->id)
            ->pluck('location_id')
            ->toArray();

        $locations = collect();
        foreach ($locationIds as $locationId) {
            $location = $this->locationRepository->find($locationId);
            if ($location) {
                $locations->push($location);
            }
        }

        return $locations;
    }

    /**
     * Get user's current location
     */
    public function getCurrentLocation(User $user): ?LocationData
    {
        if (!$this->locationRepository) {
            return null;
        }

        $currentLocationId = DB::table('user_location_preferences')
            ->where('user_id', $user->id)
            ->value('current_location_id');

        if (!$currentLocationId) {
            return null;
        }

        return $this->locationRepository->find($currentLocationId);
    }

    /**
     * Get user's default location
     */
    public function getDefaultLocation(User $user): ?LocationData
    {
        if (!$this->locationRepository) {
            return null;
        }

        $defaultLocationId = DB::table('user_location_preferences')
            ->where('user_id', $user->id)
            ->value('default_location_id');

        if (!$defaultLocationId) {
            return null;
        }

        return $this->locationRepository->find($defaultLocationId);
    }

    /**
     * Get user's primary location
     */
    public function getPrimaryLocation(User $user): ?LocationData
    {
        if (!$this->locationRepository) {
            return null;
        }

        $primaryLocationId = DB::table('location_user')
            ->where('user_id', $user->id)
            ->where('is_primary', true)
            ->value('location_id');

        if (!$primaryLocationId) {
            return null;
        }

        return $this->locationRepository->find($primaryLocationId);
    }

    /**
     * Get effective location (current > default > primary > first)
     */
    public function getEffectiveLocation(User $user): ?LocationData
    {
        // Try current location
        if ($location = $this->getCurrentLocation($user)) {
            return $location;
        }

        // Try default location
        if ($location = $this->getDefaultLocation($user)) {
            return $location;
        }

        // Try primary location
        if ($location = $this->getPrimaryLocation($user)) {
            return $location;
        }

        // Return first available location
        $locations = $this->getUserLocations($user);
        return $locations->first();
    }

    /**
     * Check if user has access to a specific location
     */
    public function hasAccessToLocation(User $user, int $locationId): bool
    {
        return DB::table('location_user')
            ->where('user_id', $user->id)
            ->where('location_id', $locationId)
            ->exists();
    }

    /**
     * Get user's role at a specific location
     */
    public function getRoleAtLocation(User $user, int $locationId): ?string
    {
        return DB::table('location_user')
            ->where('user_id', $user->id)
            ->where('location_id', $locationId)
            ->value('role');
    }

    /**
     * Check if user is a manager at any location
     */
    public function isManagerAtAnyLocation(User $user): bool
    {
        return DB::table('location_user')
            ->where('user_id', $user->id)
            ->where('role', 'manager')
            ->exists();
    }

    /**
     * Set user's current location
     */
    public function setCurrentLocation(User $user, int $locationId): bool
    {
        if (!$this->hasAccessToLocation($user, $locationId)) {
            return false;
        }

        DB::table('user_location_preferences')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'current_location_id' => $locationId,
                'updated_at' => now()
            ]
        );

        return true;
    }

    /**
     * Set user's default location
     */
    public function setDefaultLocation(User $user, int $locationId): bool
    {
        if (!$this->hasAccessToLocation($user, $locationId)) {
            return false;
        }

        DB::table('user_location_preferences')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'default_location_id' => $locationId,
                'updated_at' => now()
            ]
        );

        return true;
    }

    /**
     * Attach user to location with role
     */
    public function attachUserToLocation(
        User $user,
        int $locationId,
        string $role = 'staff',
        bool $isPrimary = false
    ): void {
        DB::table('location_user')->insertOrIgnore([
            'user_id' => $user->id,
            'location_id' => $locationId,
            'role' => $role,
            'is_primary' => $isPrimary,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Detach user from location
     */
    public function detachUserFromLocation(User $user, int $locationId): void
    {
        DB::table('location_user')
            ->where('user_id', $user->id)
            ->where('location_id', $locationId)
            ->delete();

        // Clear current/default if they were this location
        $preferences = DB::table('user_location_preferences')
            ->where('user_id', $user->id)
            ->first();

        if ($preferences) {
            $updates = [];
            if ($preferences->current_location_id === $locationId) {
                $updates['current_location_id'] = null;
            }
            if ($preferences->default_location_id === $locationId) {
                $updates['default_location_id'] = null;
            }
            
            if (!empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('user_location_preferences')
                    ->where('user_id', $user->id)
                    ->update($updates);
            }
        }
    }

    /**
     * Get locations with user's role information
     */
    public function getUserLocationsWithRoles(User $user): Collection
    {
        if (!$this->locationRepository) {
            return collect();
        }

        $pivotData = DB::table('location_user')
            ->where('user_id', $user->id)
            ->get(['location_id', 'role', 'is_primary']);

        $locationsWithRoles = collect();
        foreach ($pivotData as $pivot) {
            $location = $this->locationRepository->find($pivot->location_id);
            if ($location) {
                $locationsWithRoles->push([
                    'location' => $location,
                    'role' => $pivot->role,
                    'isPrimary' => $pivot->is_primary,
                ]);
            }
        }

        return $locationsWithRoles;
    }
}