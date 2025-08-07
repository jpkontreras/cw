<?php

declare(strict_types=1);

namespace Colame\Menu\Contracts;

use Colame\Menu\Data\MenuItemData;
use Colame\Menu\Data\MenuItemWithModifiersData;
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
    public function addToSection(int $sectionId, int $itemId, array $data = []): MenuItemData;
    
    /**
     * Update a menu item
     */
    public function update(int $id, array $data): MenuItemData;
    
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
}