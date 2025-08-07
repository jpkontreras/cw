<?php

declare(strict_types=1);

namespace Colame\Menu\Contracts;

use Colame\Menu\Data\MenuAvailabilityData;
use Spatie\LaravelData\DataCollection;

interface MenuAvailabilityInterface
{
    /**
     * Check if a menu is currently available
     */
    public function isMenuAvailable(int $menuId): bool;
    
    /**
     * Check if a menu is available at a specific time
     */
    public function isMenuAvailableAt(int $menuId, \DateTimeInterface $dateTime): bool;
    
    /**
     * Check if a menu is available at a specific location
     */
    public function isMenuAvailableAtLocation(int $menuId, int $locationId): bool;
    
    /**
     * Check if a menu section is currently available
     */
    public function isSectionAvailable(int $sectionId): bool;
    
    /**
     * Check if a menu item is currently available
     */
    public function isItemAvailable(int $menuItemId): bool;
    
    /**
     * Get availability schedule for a menu
     */
    public function getMenuAvailability(int $menuId): MenuAvailabilityData;
    
    /**
     * Get all currently available menus
     */
    public function getCurrentlyAvailableMenus(): DataCollection;
    
    /**
     * Get available menus for a location
     */
    public function getAvailableMenusForLocation(int $locationId): DataCollection;
    
    /**
     * Get next available time for a menu
     */
    public function getNextAvailableTime(int $menuId): ?\DateTimeInterface;
    
    /**
     * Check if menu meets capacity requirements
     */
    public function meetsCapacityRequirements(int $menuId, int $currentCapacity): bool;
}