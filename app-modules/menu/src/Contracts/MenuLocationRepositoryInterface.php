<?php

declare(strict_types=1);

namespace Colame\Menu\Contracts;

use Colame\Menu\Data\MenuLocationData;
use Spatie\LaravelData\DataCollection;

interface MenuLocationRepositoryInterface
{
    /**
     * Assign a menu to multiple locations
     */
    public function assignToLocations(int $menuId, array $locationIds): bool;
    
    /**
     * Remove a menu from a specific location
     */
    public function removeFromLocation(int $menuId, int $locationId): bool;
    
    /**
     * Set a menu as primary for a location
     */
    public function setPrimaryForLocation(int $menuId, int $locationId): bool;
    
    /**
     * Get all locations for a menu
     */
    public function getMenuLocations(int $menuId): DataCollection;
    
    /**
     * Check if a menu is assigned to a location
     */
    public function isAssignedToLocation(int $menuId, int $locationId): bool;
    
    /**
     * Get the primary menu for a location
     */
    public function getPrimaryMenuForLocation(int $locationId): ?int;
    
    /**
     * Update location-specific overrides for a menu
     */
    public function updateLocationOverrides(int $menuId, int $locationId, array $overrides): bool;
}