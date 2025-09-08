<?php

declare(strict_types=1);

namespace Colame\Location\Services;

use App\Models\User;
use Colame\Location\Contracts\LocationRepositoryInterface;
use Colame\Location\Contracts\LocationServiceInterface;
use Colame\Location\Data\CreateLocationData;
use Colame\Location\Data\LocationData;
use Colame\Location\Data\UpdateLocationData;
use Colame\Location\Exceptions\LocationException;
use Colame\Location\Exceptions\LocationNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

class LocationService implements LocationServiceInterface
{
    public function __construct(
        private readonly LocationRepositoryInterface $locationRepository
    ) {}

    /**
     * Create a new location.
     */
    public function createLocation(CreateLocationData $data): LocationData
    {
        DB::beginTransaction();
        try {
            // Generate location code if not provided
            $dataArray = $data->toArray();
            if (empty($dataArray['code'])) {
                $dataArray['code'] = $this->generateUniqueLocationCode();
            } else {
                // Validate code uniqueness if provided
                if ($this->locationRepository->codeExists($dataArray['code'])) {
                    throw new LocationException("Location code '{$dataArray['code']}' already exists.");
                }
            }

            $location = $this->locationRepository->create($dataArray);

            // If this is the first location or marked as default, set it as default
            if ($data->isDefault || $this->locationRepository->all()->count() === 1) {
                $this->locationRepository->setAsDefault($location->id);
            }

            DB::commit();

            $this->clearLocationCache();

            return $location;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new LocationException('Failed to create location: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique location code.
     */
    private function generateUniqueLocationCode(): string
    {
        $config = config('features.location.code_generation');
        
        if (!$config['enabled']) {
            throw new LocationException('Location code generation is disabled.');
        }

        $attempts = 0;
        do {
            $code = $this->generateLocationCode($config);
            $isUnique = !$this->locationRepository->codeExists($code);
            $attempts++;
        } while (!$isUnique && $attempts < 10);

        if (!$isUnique) {
            throw new LocationException('Failed to generate unique location code after 10 attempts.');
        }

        return $code;
    }

    /**
     * Generate a location code based on configuration.
     */
    private function generateLocationCode(array $config): string
    {
        $prefix = $config['prefix'];
        $separator = $config['separator'];
        $length = $config['length'];
        
        if ($config['use_timestamp']) {
            $suffix = date('YmdHis');
        } else {
            // Generate random alphanumeric string
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $suffix = '';
            for ($i = 0; $i < $length; $i++) {
                $suffix .= $characters[rand(0, strlen($characters) - 1)];
            }
        }
        
        return $prefix . $separator . $suffix;
    }

    /**
     * Update an existing location.
     */
    public function updateLocation(int $id, UpdateLocationData $data): LocationData
    {
        $existingLocation = $this->locationRepository->find($id);
        if (!$existingLocation) {
            throw new LocationNotFoundException("Location with ID {$id} not found.");
        }

        // Validate code uniqueness if changed
        if (isset($data->code) && $data->code !== $existingLocation->code) {
            if ($this->locationRepository->codeExists($data->code, $id)) {
                throw new LocationException("Location code '{$data->code}' already exists.");
            }
        }

        DB::beginTransaction();
        try {
            $location = $this->locationRepository->update($id, $data->toArray());

            // Handle default location update
            if (isset($data->isDefault) && $data->isDefault) {
                $this->locationRepository->setAsDefault($id);
            }

            DB::commit();

            $this->clearLocationCache();

            return $location;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new LocationException('Failed to update location: ' . $e->getMessage());
        }
    }

    /**
     * Delete a location.
     */
    public function deleteLocation(int $id): bool
    {
        $location = $this->locationRepository->find($id);
        if (!$location) {
            throw new LocationNotFoundException("Location with ID {$id} not found.");
        }

        if ($location->isDefault) {
            throw new LocationException('Cannot delete the default location.');
        }

        // Check if location has child locations
        if ($this->locationRepository->getChildLocations($id)->count() > 0) {
            throw new LocationException('Cannot delete location with child locations.');
        }

        $result = $this->locationRepository->delete($id);
        
        $this->clearLocationCache();

        return $result;
    }

    /**
     * Get a location by ID.
     */
    public function getLocation(int $id): LocationData
    {
        $location = $this->locationRepository->find($id);
        
        if (!$location) {
            throw new LocationNotFoundException("Location with ID {$id} not found.");
        }

        return $location;
    }

    /**
     * Get a location by code.
     */
    public function getLocationByCode(string $code): LocationData
    {
        $location = $this->locationRepository->findByCode($code);
        
        if (!$location) {
            throw new LocationNotFoundException("Location with code '{$code}' not found.");
        }

        return $location;
    }

    /**
     * Get all active locations (filtered by business context if available).
     */
    public function getActiveLocations(): DataCollection
    {
        // If business context is available, filter by business
        if (app()->bound('Colame\Business\Contracts\BusinessContextInterface')) {
            $businessContext = app('Colame\Business\Contracts\BusinessContextInterface');
            $currentBusinessId = $businessContext->getCurrentBusinessId();
            
            if ($currentBusinessId) {
                return Cache::remember("locations.active.business.{$currentBusinessId}", 3600, function () use ($currentBusinessId) {
                    $allLocations = $this->locationRepository->getActive();
                    // Filter locations by business ID
                    $filtered = [];
                    foreach ($allLocations as $location) {
                        if ($location->businessId === $currentBusinessId) {
                            $filtered[] = $location;
                        }
                    }
                    return LocationData::collect($filtered, DataCollection::class);
                });
            }
        }
        
        // Fallback to all active locations if no business context
        return Cache::remember('locations.active', 3600, function () {
            return $this->locationRepository->getActive();
        });
    }

    /**
     * Get all locations accessible by a user (filtered by business context if available).
     */
    public function getUserAccessibleLocations(int $userId): DataCollection
    {
        $locations = $this->locationRepository->getUserLocations($userId);
        
        // If business context is available, filter by business
        if (app()->bound('Colame\Business\Contracts\BusinessContextInterface')) {
            $businessContext = app('Colame\Business\Contracts\BusinessContextInterface');
            $currentBusinessId = $businessContext->getCurrentBusinessId();
            
            if ($currentBusinessId) {
                // Filter locations by business ID
                $filtered = [];
                foreach ($locations as $location) {
                    if (!$location->businessId || $location->businessId === $currentBusinessId) {
                        $filtered[] = $location;
                    }
                }
                return LocationData::collect($filtered, DataCollection::class);
            }
        }
        
        return $locations;
    }

    /**
     * Assign a user to a location.
     */
    public function assignUserToLocation(int $userId, int $locationId, string $role = 'staff', bool $isPrimary = false): void
    {
        // Verify location exists
        $location = $this->getLocation($locationId);

        // Verify user exists
        $user = User::findOrFail($userId);

        $this->locationRepository->assignUser($locationId, $userId, $role, $isPrimary);

        // If primary, update user's default location in preferences table
        if ($isPrimary) {
            DB::table('user_location_preferences')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'default_location_id' => $locationId,
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Remove a user from a location.
     */
    public function removeUserFromLocation(int $userId, int $locationId): void
    {
        $this->locationRepository->removeUser($locationId, $userId);

        // Clear current/default if they were this location
        $preferences = DB::table('user_location_preferences')
            ->where('user_id', $userId)
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
                    ->where('user_id', $userId)
                    ->update($updates);
            }
        }
    }

    /**
     * Set user's current location.
     */
    public function setUserCurrentLocation(int $userId, int $locationId): void
    {
        // Verify user has access to the location
        if (!$this->userHasAccessToLocation($userId, $locationId)) {
            throw new LocationException('User does not have access to this location.');
        }

        // Verify location belongs to current business context if business module is available
        if (app()->bound('Colame\Business\Contracts\BusinessContextInterface')) {
            $businessContext = app('Colame\Business\Contracts\BusinessContextInterface');
            $currentBusinessId = $businessContext->getCurrentBusinessId();
            
            if ($currentBusinessId) {
                $location = $this->locationRepository->find($locationId);
                if ($location && $location->businessId && $location->businessId !== $currentBusinessId) {
                    throw new LocationException('Location does not belong to the current business context.');
                }
            }
        }

        // Update or insert into user_location_preferences table
        DB::table('user_location_preferences')->updateOrInsert(
            ['user_id' => $userId],
            [
                'current_location_id' => $locationId,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Set user's default location.
     */
    public function setUserDefaultLocation(int $userId, int $locationId): void
    {
        // Verify user has access to the location
        if (!$this->userHasAccessToLocation($userId, $locationId)) {
            throw new LocationException('User does not have access to this location.');
        }

        // Verify location belongs to current business context if business module is available
        if (app()->bound('Colame\Business\Contracts\BusinessContextInterface')) {
            $businessContext = app('Colame\Business\Contracts\BusinessContextInterface');
            $currentBusinessId = $businessContext->getCurrentBusinessId();
            
            if ($currentBusinessId) {
                $location = $this->locationRepository->find($locationId);
                if ($location && $location->businessId && $location->businessId !== $currentBusinessId) {
                    throw new LocationException('Location does not belong to the current business context.');
                }
            }
        }

        // Update or insert into user_location_preferences table
        DB::table('user_location_preferences')->updateOrInsert(
            ['user_id' => $userId],
            [
                'default_location_id' => $locationId,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Get user's current location.
     */
    public function getUserCurrentLocation(int $userId): ?LocationData
    {
        // Get from user_location_preferences table
        $currentLocationId = DB::table('user_location_preferences')
            ->where('user_id', $userId)
            ->value('current_location_id');
        
        if (!$currentLocationId) {
            return null;
        }

        return $this->locationRepository->find($currentLocationId);
    }

    /**
     * Get user's default location.
     */
    public function getUserDefaultLocation(int $userId): ?LocationData
    {
        // Get from user_location_preferences table
        $defaultLocationId = DB::table('user_location_preferences')
            ->where('user_id', $userId)
            ->value('default_location_id');
        
        if (!$defaultLocationId) {
            return null;
        }

        return $this->locationRepository->find($defaultLocationId);
    }

    /**
     * Set location as system default.
     */
    public function setLocationAsDefault(int $id): void
    {
        $this->getLocation($id); // Verify location exists
        
        $this->locationRepository->setAsDefault($id);
        
        $this->clearLocationCache();
    }

    /**
     * Get system default location.
     */
    public function getDefaultLocation(): LocationData
    {
        $location = Cache::remember('location.default', 3600, function () {
            return $this->locationRepository->getDefault();
        });

        if (!$location) {
            throw new LocationException('No default location configured.');
        }

        return $location;
    }

    /**
     * Check if user has access to location.
     */
    public function userHasAccessToLocation(int $userId, int $locationId): bool
    {
        $userLocations = $this->getUserAccessibleLocations($userId);
        
        return $userLocations->contains('id', $locationId);
    }

    /**
     * Get location hierarchy tree.
     */
    public function getLocationHierarchy(): DataCollection
    {
        return Cache::remember('locations.hierarchy', 3600, function () {
            return $this->locationRepository->getWithHierarchy();
        });
    }

    /**
     * Update location settings.
     */
    public function updateLocationSettings(int $locationId, array $settings): void
    {
        $this->getLocation($locationId); // Verify location exists

        foreach ($settings as $key => $value) {
            $type = $this->determineSettingType($value);
            $this->locationRepository->updateSetting($locationId, $key, $value, $type);
        }
    }

    /**
     * Get location settings.
     */
    public function getLocationSettings(int $locationId): array
    {
        return $this->locationRepository->getSettings($locationId);
    }

    /**
     * Check if location is open.
     */
    public function isLocationOpen(int $locationId): bool
    {
        $location = $this->locationRepository->find($locationId);
        
        return $location ? $location->isOpen() : false;
    }

    /**
     * Get locations by capability.
     */
    public function getLocationsByCapability(string $capability): DataCollection
    {
        return $this->locationRepository->getByCapability($capability);
    }

    /**
     * Validate location code uniqueness.
     */
    public function validateLocationCode(string $code, ?int $excludeId = null): bool
    {
        return !$this->locationRepository->codeExists($code, $excludeId);
    }

    /**
     * Get location statistics.
     */
    public function getLocationStatistics(int $locationId): array
    {
        $stats = $this->locationRepository->getLocationStatistics($locationId);
        
        if ($stats['total_users'] === 0 && $stats['total_child_locations'] === 0) {
            // Check if location exists
            $location = $this->locationRepository->find($locationId);
            if (!$location) {
                throw new LocationNotFoundException("Location with ID {$locationId} not found.");
            }
        }

        return $stats;
    }

    /**
     * Get locations by type.
     */
    public function getLocationsByType(string $type): DataCollection
    {
        return $this->locationRepository->findByType($type);
    }
    
    /**
     * Clear location-related cache.
     */
    private function clearLocationCache(): void
    {
        Cache::forget('locations.active');
        Cache::forget('locations.hierarchy');
        Cache::forget('location.default');
    }

    /**
     * Determine setting type based on value.
     */
    private function determineSettingType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value)) {
            return 'array';
        } elseif (is_object($value)) {
            return 'object';
        }
        
        return 'string';
    }
}