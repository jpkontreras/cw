<?php

namespace Colame\Item\Contracts;

use App\Core\Contracts\FilterableRepositoryInterface;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use Illuminate\Support\Collection;

interface ItemRepositoryInterface extends FilterableRepositoryInterface
{
    /**
     * Find an item by ID
     */
    public function find(int $id): ?ItemData;
    
    /**
     * Find an item with all relations
     */
    public function findWithRelations(int $id): ?ItemWithRelationsData;
    
    /**
     * Find an item by slug
     */
    public function findBySlug(string $slug): ?ItemData;
    
    /**
     * Get all active items
     */
    public function getActiveItems(): Collection;
    
    /**
     * Get active items for a specific location
     */
    public function getActiveItemsForLocation(int $locationId): Collection;
    
    /**
     * Check if an item is available
     */
    public function checkAvailability(int $id, int $quantity = 1): bool;
    
    /**
     * Get the current price for an item
     */
    public function getCurrentPrice(int $id, ?int $locationId = null, ?int $variantId = null): float;
    
    /**
     * Get items by category
     */
    public function getByCategory(int $categoryId, bool $activeOnly = true): Collection;
    
    /**
     * Get items by multiple categories
     */
    public function getByCategories(array $categoryIds, bool $activeOnly = true): Collection;
    
    /**
     * Get featured items
     */
    public function getFeaturedItems(int $limit = 10): Collection;
    
    /**
     * Get low stock items
     */
    public function getLowStockItems(?int $locationId = null): Collection;
    
    /**
     * Create a new item
     */
    public function create(array $data): ItemData;
    
    /**
     * Update an item
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Update an item and return the updated data
     */
    public function updateAndReturn(int $id, array $data): ItemData;
    
    /**
     * Update stock quantity
     */
    public function updateStock(int $id, int $quantity, ?int $variantId = null, ?int $locationId = null): bool;
    
    /**
     * Decrement stock quantity
     */
    public function decrementStock(int $id, int $quantity, ?int $variantId = null, ?int $locationId = null): bool;
    
    /**
     * Increment stock quantity
     */
    public function incrementStock(int $id, int $quantity, ?int $variantId = null, ?int $locationId = null): bool;
    
    /**
     * Soft delete an item
     */
    public function delete(int $id): bool;
    
    /**
     * Restore a soft deleted item
     */
    public function restore(int $id): bool;
    
    /**
     * Duplicate an item
     */
    public function duplicate(int $id, array $overrides = []): ItemData;
    
    /**
     * Get filter options for a specific field
     */
    public function getFilterOptions(string $field): array;
}