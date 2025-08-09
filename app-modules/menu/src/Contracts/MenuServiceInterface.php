<?php

declare(strict_types=1);

namespace Colame\Menu\Contracts;

use Colame\Menu\Data\CreateMenuData;
use Colame\Menu\Data\UpdateMenuData;
use Colame\Menu\Data\MenuData;
use Colame\Menu\Data\MenuStructureData;
use Colame\Menu\Data\SaveMenuStructureData;
use Spatie\LaravelData\DataCollection;

interface MenuServiceInterface
{
    /**
     * Create a new menu
     */
    public function createMenu(CreateMenuData $data): MenuData;
    
    /**
     * Update a menu
     */
    public function updateMenu(int $id, UpdateMenuData $data): MenuData;
    
    /**
     * Delete a menu
     */
    public function deleteMenu(int $id): bool;
    
    /**
     * Get complete menu structure with sections and items
     */
    public function getMenuStructure(int $id): MenuStructureData;
    
    /**
     * Get menu structure for a specific location
     */
    public function getMenuStructureForLocation(int $menuId, int $locationId): MenuStructureData;
    
    /**
     * Save complete menu structure with sections and items
     */
    public function saveMenuStructure(int $menuId, SaveMenuStructureData $data): MenuStructureData;
    
    /**
     * Duplicate a menu
     */
    public function duplicateMenu(int $id, string $newName): MenuData;
    
    /**
     * Build menu from template
     */
    public function buildFromTemplate(string $templateName, array $customizations = []): MenuData;
    
    /**
     * Assign menu to locations
     */
    public function assignToLocations(int $menuId, array $locationIds): bool;
    
    /**
     * Remove menu from location
     */
    public function removeFromLocation(int $menuId, int $locationId): bool;
    
    /**
     * Set menu as primary for location
     */
    public function setPrimaryForLocation(int $menuId, int $locationId): bool;
    
    /**
     * Validate menu structure
     */
    public function validateMenuStructure(int $menuId): array;
    
    /**
     * Get menu analytics
     */
    public function getMenuAnalytics(int $menuId, \DateTimeInterface $from, \DateTimeInterface $to): array;
    
    /**
     * Export menu to format (json, pdf, csv)
     */
    public function exportMenu(int $menuId, string $format): string;
    
    /**
     * Import menu from file
     */
    public function importMenu(string $filePath, string $format): MenuData;
}