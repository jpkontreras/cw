<?php

declare(strict_types=1);

namespace Colame\Menu\Repositories;

use Colame\Menu\Contracts\MenuLocationRepositoryInterface;
use Colame\Menu\Data\MenuLocationData;
use Colame\Menu\Models\Menu;
use Colame\Menu\Models\MenuLocation;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

class MenuLocationRepository implements MenuLocationRepositoryInterface
{
    /**
     * Assign a menu to multiple locations
     */
    public function assignToLocations(int $menuId, array $locationIds): bool
    {
        $menu = Menu::findOrFail($menuId);
        
        foreach ($locationIds as $locationId) {
            $menu->locations()->updateOrCreate(
                ['location_id' => $locationId],
                ['is_active' => true, 'activated_at' => now()]
            );
        }
        
        return true;
    }
    
    /**
     * Remove a menu from a specific location
     */
    public function removeFromLocation(int $menuId, int $locationId): bool
    {
        return Menu::find($menuId)
            ->locations()
            ->where('location_id', $locationId)
            ->delete() > 0;
    }
    
    /**
     * Set a menu as primary for a location
     */
    public function setPrimaryForLocation(int $menuId, int $locationId): bool
    {
        DB::transaction(function () use ($menuId, $locationId) {
            // Remove primary status from other menus at this location
            MenuLocation::where('location_id', $locationId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
            
            // Set this menu as primary
            MenuLocation::where('menu_id', $menuId)
                ->where('location_id', $locationId)
                ->update(['is_primary' => true]);
        });
        
        return true;
    }
    
    /**
     * Get all locations for a menu
     */
    public function getMenuLocations(int $menuId): DataCollection
    {
        $locations = MenuLocation::where('menu_id', $menuId)->get();
        return MenuLocationData::collect($locations, DataCollection::class);
    }
    
    /**
     * Check if a menu is assigned to a location
     */
    public function isAssignedToLocation(int $menuId, int $locationId): bool
    {
        return MenuLocation::where('menu_id', $menuId)
            ->where('location_id', $locationId)
            ->exists();
    }
    
    /**
     * Get the primary menu for a location
     */
    public function getPrimaryMenuForLocation(int $locationId): ?int
    {
        $location = MenuLocation::where('location_id', $locationId)
            ->where('is_primary', true)
            ->first();
        
        return $location ? $location->menu_id : null;
    }
    
    /**
     * Update location-specific overrides for a menu
     */
    public function updateLocationOverrides(int $menuId, int $locationId, array $overrides): bool
    {
        return MenuLocation::where('menu_id', $menuId)
            ->where('location_id', $locationId)
            ->update(['overrides' => $overrides]) > 0;
    }
}