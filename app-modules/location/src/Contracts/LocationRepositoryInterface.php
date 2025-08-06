<?php

declare(strict_types=1);

namespace Colame\Location\Contracts;

use Colame\Location\Data\LocationData;
use Spatie\LaravelData\DataCollection;

interface LocationRepositoryInterface
{
    /**
     * Find a location by ID.
     */
    public function find(int $id): ?LocationData;

    /**
     * Find a location by code.
     */
    public function findByCode(string $code): ?LocationData;

    /**
     * Get all active locations.
     */
    public function getActive(): DataCollection;

    /**
     * Get all locations.
     */
    public function all(): DataCollection;

    /**
     * Get locations accessible by a user.
     */
    public function getUserLocations(int $userId): DataCollection;

    /**
     * Get locations with their hierarchy.
     */
    public function getWithHierarchy(): DataCollection;

    /**
     * Get the default location.
     */
    public function getDefault(): ?LocationData;

    /**
     * Create a new location.
     */
    public function create(array $data): LocationData;

    /**
     * Update a location.
     */
    public function update(int $id, array $data): LocationData;

    /**
     * Delete a location.
     */
    public function delete(int $id): bool;

    /**
     * Check if a location code exists.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool;

    /**
     * Get locations by type.
     */
    public function getByType(string $type): DataCollection;

    /**
     * Get child locations of a parent.
     */
    public function getChildLocations(int $parentId): DataCollection;

    /**
     * Get locations with specific capability.
     */
    public function getByCapability(string $capability): DataCollection;

    /**
     * Assign a user to a location.
     */
    public function assignUser(int $locationId, int $userId, string $role = 'staff', bool $isPrimary = false): void;

    /**
     * Remove a user from a location.
     */
    public function removeUser(int $locationId, int $userId): void;

    /**
     * Update user's role at a location.
     */
    public function updateUserRole(int $locationId, int $userId, string $role): void;

    /**
     * Set location as default.
     */
    public function setAsDefault(int $id): void;

    /**
     * Get location settings.
     */
    public function getSettings(int $locationId): array;

    /**
     * Update location setting.
     */
    public function updateSetting(int $locationId, string $key, $value, string $type = 'string'): void;

    /**
     * Get location statistics.
     */
    public function getLocationStatistics(int $locationId): array;
    
    /**
     * Find locations by type.
     */
    public function findByType(string $type): DataCollection;
}