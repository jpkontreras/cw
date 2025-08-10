<?php

declare(strict_types=1);

namespace Colame\Menu\Contracts;

use Colame\Menu\Data\MenuItemData;
use Colame\Menu\Data\MenuItemWithModifiersData;
use Colame\Menu\Data\CreateMenuItemData;
use Colame\Menu\Data\UpdateMenuItemData;
use Spatie\LaravelData\DataCollection;

interface MenuItemRepositoryInterface
{
    /**
     * Find a menu item by ID
     */
    public function find(int $id): ?MenuItemData;
    
    /**
     * Find a menu item with modifiers
     */
    public function findWithModifiers(int $id): ?MenuItemWithModifiersData;
    
    /**
     * Get all items for a menu
     */
    public function getByMenu(int $menuId): DataCollection;
    
    /**
     * Get items for a section
     */
    public function getBySection(int $sectionId): DataCollection;
    
    /**
     * Get active items for a section
     */
    public function getActiveBySection(int $sectionId): DataCollection;
    
    /**
     * Get featured items for a menu
     */
    public function getFeaturedByMenu(int $menuId): DataCollection;
    
    /**
     * Find menu item by menu and item IDs
     */
    public function findByMenuAndItem(int $menuId, int $itemId): ?MenuItemData;
    
    /**
     * Add item to menu section
     */
    public function addToSection(int $sectionId, int $itemId, ?CreateMenuItemData $data = null): MenuItemData;
    
    /**
     * Update a menu item
     */
    public function update(int $id, UpdateMenuItemData $data): MenuItemData;
    
    /**
     * Remove item from menu
     */
    public function removeFromMenu(int $menuId, int $itemId): bool;
    
    /**
     * Move item to different section
     */
    public function moveToSection(int $id, int $sectionId): bool;
    
    /**
     * Update item order
     */
    public function updateOrder(int $id, int $order): bool;
    
    /**
     * Bulk update item orders
     */
    public function bulkUpdateOrder(array $orders): bool;
    
    /**
     * Set item as featured
     */
    public function setFeatured(int $id, bool $featured = true): bool;
    
    /**
     * Update item availability
     */
    public function setAvailability(int $id, bool $available): bool;
    
    /**
     * Find by ID in specific menu and section
     */
    public function findByIdInMenuAndSection(int $id, int $menuId, int $sectionId): ?MenuItemData;
    
    /**
     * Find by item ID in specific menu and section
     */
    public function findByItemIdInMenuAndSection(int $itemId, int $menuId, int $sectionId): ?MenuItemData;
    
    /**
     * Create a menu item
     */
    public function create(CreateMenuItemData $data): MenuItemData;
    
    /**
     * Delete a menu item
     */
    public function delete(int $id): bool;
    
    /**
     * Update or create a menu item
     */
    public function updateOrCreate(array $attributes, array $values): MenuItemData;
    
    /**
     * Delete all items not in the given list of IDs for a menu
     */
    public function deleteExcept(int $menuId, array $exceptIds): int;
    
    /**
     * Create item in a specific section
     */
    public function createInSection(int $sectionId, CreateMenuItemData $data): MenuItemData;
    
    /**
     * Delete item from a specific section
     */
    public function deleteFromSection(int $sectionId, int $itemId): bool;
    
    /**
     * Find all items by section ID
     */
    public function findBySection(int $sectionId): DataCollection;
    
    /**
     * Get items by menu with relations
     */
    public function getByMenuWithRelations(int $menuId, array $relations = []): DataCollection;
    
    /**
     * Get featured and available items by menu
     */
    public function getFeaturedAvailableByMenu(int $menuId): DataCollection;
    
    /**
     * Check if section exists in menu
     */
    public function sectionExistsInMenu(int $sectionId, int $menuId): bool;
    
    /**
     * Find item by ID in menu (checks if item belongs to menu via section)
     */
    public function findByIdInMenu(int $id, int $menuId): ?MenuItemData;
    
    /**
     * Delete item by ID if it belongs to menu
     */
    public function deleteByIdInMenu(int $id, int $menuId): bool;
    
    /**
     * Get items by section with relations
     */
    public function getBySectionWithRelations(int $sectionId, int $menuId, array $relations = []): DataCollection;
    
    /**
     * Find item with relations
     */
    public function findWithRelations(int $id, int $menuId, int $sectionId): ?MenuItemData;
    
    /**
     * Toggle item availability
     */
    public function toggleAvailability(int $id, int $menuId, int $sectionId): ?MenuItemData;
    
    /**
     * Bulk update availability
     */
    public function bulkUpdateAvailability(array $items, int $menuId): int;
    
    /**
     * Get popular items (featured and available)
     */
    public function getPopularByMenu(int $menuId, int $limit = 10): DataCollection;
    
    /**
     * Search items in menu
     */
    public function searchInMenu(int $menuId, string $query, ?int $sectionId = null): DataCollection;
    
    /**
     * Check if item is active
     */
    public function isActive(int $id): bool;
}