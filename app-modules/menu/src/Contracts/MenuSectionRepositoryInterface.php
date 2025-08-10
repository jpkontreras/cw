<?php

declare(strict_types=1);

namespace Colame\Menu\Contracts;

use Colame\Menu\Data\CreateMenuSectionData;
use Colame\Menu\Data\MenuSectionData;
use Colame\Menu\Data\MenuSectionWithItemsData;
use Colame\Menu\Data\UpdateMenuSectionData;
use Spatie\LaravelData\DataCollection;

interface MenuSectionRepositoryInterface
{
    /**
     * Find a section by ID
     */
    public function find(int $id): ?MenuSectionData;
    
    /**
     * Find a section with items
     */
    public function findWithItems(int $id): ?MenuSectionWithItemsData;
    
    /**
     * Get all sections for a menu
     */
    public function getByMenu(int $menuId): DataCollection;
    
    /**
     * Get root sections for a menu (no parent)
     */
    public function getRootSectionsByMenu(int $menuId): DataCollection;
    
    /**
     * Get child sections
     */
    public function getChildren(int $parentId): DataCollection;
    
    /**
     * Get active sections for a menu
     */
    public function getActiveSectionsByMenu(int $menuId): DataCollection;
    
    /**
     * Create a new section
     */
    public function create(CreateMenuSectionData $data): MenuSectionData;
    
    /**
     * Update a section
     */
    public function update(int $id, UpdateMenuSectionData $data): MenuSectionData;
    
    /**
     * Delete a section
     */
    public function delete(int $id): bool;
    
    /**
     * Move section to different parent
     */
    public function moveToParent(int $id, ?int $parentId): bool;
    
    /**
     * Update section order
     */
    public function updateOrder(int $id, int $order): bool;
    
    /**
     * Bulk update section orders
     */
    public function bulkUpdateOrder(array $orders): bool;
    
    /**
     * Find section by ID and menu ID
     */
    public function findByIdAndMenuId(int $id, int $menuId): ?MenuSectionData;
    
    /**
     * Create or update a section
     */
    public function updateOrCreate(array $attributes, array $values): MenuSectionData;
    
    /**
     * Delete all sections not in the given list of IDs for a menu
     */
    public function deleteExcept(int $menuId, array $exceptIds): int;
    
    /**
     * Check if section is active
     */
    public function isActive(int $id): bool;
}