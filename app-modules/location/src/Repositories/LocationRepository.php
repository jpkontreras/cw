<?php

declare(strict_types=1);

namespace Colame\Location\Repositories;

use Colame\Location\Contracts\LocationRepositoryInterface;
use Colame\Location\Data\LocationData;
use Colame\Location\Data\LocationSettingsData;
use Colame\Location\Models\Location;
use Colame\Location\Models\LocationSetting;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

class LocationRepository implements LocationRepositoryInterface
{
    /**
     * Find a location by ID.
     */
    public function find(int $id): ?LocationData
    {
        $location = Location::find($id);
        
        return $location ? LocationData::fromModel($location) : null;
    }

    /**
     * Find a location by code.
     */
    public function findByCode(string $code): ?LocationData
    {
        $location = Location::where('code', $code)->first();
        
        return $location ? LocationData::fromModel($location) : null;
    }

    /**
     * Get all active locations.
     */
    public function getActive(): DataCollection
    {
        return LocationData::collect(
            Location::active()->orderBy('name')->get(),
            DataCollection::class
        );
    }

    /**
     * Get all locations.
     */
    public function all(): DataCollection
    {
        return LocationData::collect(
            Location::orderBy('name')->get(),
            DataCollection::class
        );
    }

    /**
     * Get locations accessible by a user.
     */
    public function getUserLocations(int $userId): DataCollection
    {
        $locations = Location::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->orderBy('name')->get();
        
        return LocationData::collect($locations, DataCollection::class);
    }

    /**
     * Get locations with their hierarchy.
     */
    public function getWithHierarchy(): DataCollection
    {
        $locations = Location::with('childLocations')->whereNull('parent_location_id')
            ->orderBy('name')->get();
        
        return LocationData::collect($locations, DataCollection::class);
    }

    /**
     * Get the default location.
     */
    public function getDefault(): ?LocationData
    {
        $location = Location::default()->first();
        
        return $location ? LocationData::fromModel($location) : null;
    }

    /**
     * Create a new location.
     */
    public function create(array $data): LocationData
    {
        $location = Location::create($data);
        $location->refresh();
        
        return LocationData::fromModel($location);
    }

    /**
     * Update a location.
     */
    public function update(int $id, array $data): LocationData
    {
        $location = Location::findOrFail($id);
        $location->update($data);
        
        return LocationData::fromModel($location->fresh());
    }

    /**
     * Delete a location.
     */
    public function delete(int $id): bool
    {
        $location = Location::findOrFail($id);
        
        return $location->delete();
    }

    /**
     * Check if a location code exists.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = Location::where('code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get locations by type.
     */
    public function getByType(string $type): DataCollection
    {
        return LocationData::collect(
            Location::where('type', $type)->orderBy('name')->get(),
            DataCollection::class
        );
    }

    /**
     * Get child locations of a parent.
     */
    public function getChildLocations(int $parentId): DataCollection
    {
        return LocationData::collect(
            Location::where('parent_location_id', $parentId)->orderBy('name')->get(),
            DataCollection::class
        );
    }

    /**
     * Get locations with specific capability.
     */
    public function getByCapability(string $capability): DataCollection
    {
        return LocationData::collect(
            Location::whereJsonContains('capabilities', $capability)->orderBy('name')->get(),
            DataCollection::class
        );
    }

    /**
     * Assign a user to a location.
     */
    public function assignUser(int $locationId, int $userId, string $role = 'staff', bool $isPrimary = false): void
    {
        $location = Location::findOrFail($locationId);
        
        // If setting as primary, remove primary flag from other locations
        if ($isPrimary) {
            DB::table('location_user')
                ->where('user_id', $userId)
                ->update(['is_primary' => false]);
        }
        
        $location->users()->syncWithoutDetaching([
            $userId => [
                'role' => $role,
                'is_primary' => $isPrimary,
            ]
        ]);
    }

    /**
     * Remove a user from a location.
     */
    public function removeUser(int $locationId, int $userId): void
    {
        $location = Location::findOrFail($locationId);
        $location->users()->detach($userId);
    }

    /**
     * Update user's role at a location.
     */
    public function updateUserRole(int $locationId, int $userId, string $role): void
    {
        DB::table('location_user')
            ->where('location_id', $locationId)
            ->where('user_id', $userId)
            ->update(['role' => $role]);
    }

    /**
     * Set location as default.
     */
    public function setAsDefault(int $id): void
    {
        DB::transaction(function () use ($id) {
            // Remove default flag from all locations
            Location::where('is_default', true)->update(['is_default' => false]);
            
            // Set new default
            Location::where('id', $id)->update(['is_default' => true]);
        });
    }

    /**
     * Get location settings.
     */
    public function getSettings(int $locationId): array
    {
        $settings = LocationSetting::where('location_id', $locationId)->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getValue();
        }
        
        return $result;
    }

    /**
     * Update location setting.
     */
    public function updateSetting(int $locationId, string $key, $value, string $type = 'string'): void
    {
        $location = Location::findOrFail($locationId);
        
        $location->setSetting($key, $value, $type);
    }

    /**
     * Get location statistics.
     */
    public function getLocationStatistics(int $locationId): array
    {
        $location = Location::with(['users', 'childLocations'])->find($locationId);
        
        if (!$location) {
            return [
                'total_users' => 0,
                'total_child_locations' => 0,
                'users_by_role' => [],
                'is_open' => false,
            ];
        }

        $usersByRole = $location->users->groupBy('pivot.role')->map->count();

        return [
            'total_users' => $location->users->count(),
            'total_child_locations' => $location->childLocations->count(),
            'users_by_role' => $usersByRole->toArray(),
            'is_open' => $location->isOpen(),
        ];
    }
    
    /**
     * Find locations by type.
     */
    public function findByType(string $type): DataCollection
    {
        $locations = Location::where('type', $type)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
            
        return LocationData::collect($locations, DataCollection::class);
    }

    /**
     * Get currency configuration for a location.
     * Returns the currency config from money.php for the location's currency.
     */
    public function getCurrencyConfig(int $locationId): ?array
    {
        $location = Location::find($locationId);
        
        if (!$location) {
            return null;
        }
        
        $currencyCode = $location->currency ?: 'CLP';
        $config = config("money.currencies.{$currencyCode}");
        
        if (!$config) {
            // Fallback to CLP if currency not found
            $config = config('money.currencies.CLP');
        }
        
        return [
            'code' => $currencyCode,
            'name' => $config['name'] ?? 'Chilean Peso',
            'precision' => $config['precision'] ?? 0,
            'subunit' => $config['subunit'] ?? 1,
            'symbol' => $config['symbol'] ?? '$',
            'symbol_first' => $config['symbol_first'] ?? true,
            'decimal_mark' => $config['decimal_mark'] ?? ',',
            'thousands_separator' => $config['thousands_separator'] ?? '.',
        ];
    }
}