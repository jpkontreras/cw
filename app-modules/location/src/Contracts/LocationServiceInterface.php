<?php

declare(strict_types=1);

namespace Colame\Location\Contracts;

use Colame\Location\Data\CreateLocationData;
use Colame\Location\Data\LocationData;
use Colame\Location\Data\UpdateLocationData;
use Spatie\LaravelData\DataCollection;

interface LocationServiceInterface
{
    /**
     * Create a new location.
     */
    public function createLocation(CreateLocationData $data): LocationData;

    /**
     * Update an existing location.
     */
    public function updateLocation(int $id, UpdateLocationData $data): LocationData;

    /**
     * Delete a location.
     */
    public function deleteLocation(int $id): bool;

    /**
     * Get a location by ID.
     */
    public function getLocation(int $id): LocationData;

    /**
     * Get a location by code.
     */
    public function getLocationByCode(string $code): LocationData;

    /**
     * Get all active locations.
     */
    public function getActiveLocations(): DataCollection;

    /**
     * Get all locations accessible by a user.
     */
    public function getUserAccessibleLocations(int $userId): DataCollection;

    /**
     * Assign a user to a location.
     */
    public function assignUserToLocation(int $userId, int $locationId, string $role = 'staff', bool $isPrimary = false): void;

    /**
     * Remove a user from a location.
     */
    public function removeUserFromLocation(int $userId, int $locationId): void;

    /**
     * Set user's current location.
     */
    public function setUserCurrentLocation(int $userId, int $locationId): void;

    /**
     * Set user's default location.
     */
    public function setUserDefaultLocation(int $userId, int $locationId): void;

    /**
     * Get user's current location.
     */
    public function getUserCurrentLocation(int $userId): ?LocationData;

    /**
     * Get user's default location.
     */
    public function getUserDefaultLocation(int $userId): ?LocationData;

    /**
     * Set location as system default.
     */
    public function setLocationAsDefault(int $id): void;

    /**
     * Get system default location.
     */
    public function getDefaultLocation(): LocationData;

    /**
     * Check if user has access to location.
     */
    public function userHasAccessToLocation(int $userId, int $locationId): bool;

    /**
     * Get location hierarchy tree.
     */
    public function getLocationHierarchy(): DataCollection;

    /**
     * Update location settings.
     */
    public function updateLocationSettings(int $locationId, array $settings): void;

    /**
     * Get location settings.
     */
    public function getLocationSettings(int $locationId): array;

    /**
     * Check if location is open.
     */
    public function isLocationOpen(int $locationId): bool;

    /**
     * Get locations by capability.
     */
    public function getLocationsByCapability(string $capability): DataCollection;

    /**
     * Validate location code uniqueness.
     */
    public function validateLocationCode(string $code, ?int $excludeId = null): bool;

    /**
     * Get location statistics.
     */
    public function getLocationStatistics(int $locationId): array;
    
    /**
     * Get locations by type.
     */
    public function getLocationsByType(string $type): DataCollection;
}