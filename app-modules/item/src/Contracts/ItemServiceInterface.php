<?php

namespace Colame\Item\Contracts;

use App\Core\Data\PaginatedResourceData;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use Colame\Item\Data\PriceCalculationData;
use Illuminate\Support\Collection;

interface ItemServiceInterface
{
    /**
     * Get paginated items with filters
     */
    public function getPaginatedItems(array $filters = [], int $perPage = 20): PaginatedResourceData;
    
    /**
     * Get a single item by ID
     */
    public function getItem(int $id): ItemWithRelationsData;
    
    /**
     * Get items for public display (active, available)
     */
    public function getPublicItems(array $filters = []): Collection;
    
    /**
     * Create a new item
     */
    public function createItem(array $data): ItemData;
    
    /**
     * Update an existing item
     */
    public function updateItem(int $id, array $data): ItemData;
    
    /**
     * Delete an item
     */
    public function deleteItem(int $id): bool;
    
    /**
     * Duplicate an item
     */
    public function duplicateItem(int $id, array $overrides = []): ItemData;
    
    /**
     * Check item availability
     */
    public function checkAvailability(int $itemId, int $quantity, ?int $variantId = null, ?int $locationId = null): bool;
    
    /**
     * Calculate item price with modifiers
     */
    public function calculatePrice(int $itemId, ?int $variantId = null, array $modifierIds = [], ?int $locationId = null): PriceCalculationData;
    
    /**
     * Bulk update items
     */
    public function bulkUpdate(array $itemIds, array $data): int;
    
    /**
     * Import items from file
     */
    public function importItems(string $filePath, array $options = []): array;
    
    /**
     * Export items to file
     */
    public function exportItems(array $filters = [], string $format = 'csv'): string;
    
    /**
     * Get low stock items
     */
    public function getLowStockItems(?int $locationId = null): Collection;
    
    /**
     * Update item stock
     */
    public function updateStock(int $itemId, int $quantity, string $reason, ?int $variantId = null, ?int $locationId = null): bool;
}