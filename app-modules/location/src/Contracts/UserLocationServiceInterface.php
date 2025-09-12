<?php

declare(strict_types=1);

namespace Colame\Location\Contracts;

use App\Models\User;
use Colame\Location\Data\LocationData;
use Illuminate\Support\Collection;

/**
 * Interface for user-location relationship management
 */
interface UserLocationServiceInterface
{
    /**
     * Get all locations accessible by a user
     */
    public function getUserLocations(User $user): Collection;

    /**
     * Get user's current location
     */
    public function getCurrentLocation(User $user): ?LocationData;

    /**
     * Get user's default location
     */
    public function getDefaultLocation(User $user): ?LocationData;

    /**
     * Get user's primary location
     */
    public function getPrimaryLocation(User $user): ?LocationData;

    /**
     * Get effective location for a user
     * Fallback chain: current → default → primary → first available
     */
    public function getEffectiveLocation(User $user): ?LocationData;

    /**
     * Check if user has access to a specific location
     */
    public function hasAccessToLocation(User $user, int $locationId): bool;

    /**
     * Set user's current location
     */
    public function setCurrentLocation(User $user, int $locationId): bool;

    /**
     * Set user's default location
     */
    public function setDefaultLocation(User $user, int $locationId): bool;
}