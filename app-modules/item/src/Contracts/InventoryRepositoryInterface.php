<?php

namespace Colame\Item\Contracts;

use App\Core\Contracts\FilterableRepositoryInterface;
use Colame\Item\Data\InventoryMovementData;
use Colame\Item\Data\ItemLocationStockData;
use Illuminate\Support\Collection;

interface InventoryRepositoryInterface extends FilterableRepositoryInterface
{
    /**
     * Get current stock level for an item
     */
    public function getStockLevel(int $itemId, ?int $variantId = null, ?int $locationId = null): float;
    
    /**
     * Get available stock (quantity minus reserved)
     */
    public function getAvailableStock(int $itemId, ?int $variantId = null, ?int $locationId = null): float;
    
    /**
     * Get stock levels across all locations
     */
    public function getStockByLocations(int $itemId, ?int $variantId = null): Collection;
    
    /**
     * Create a stock movement record
     */
    public function createMovement(array $data): InventoryMovementData;
    
    /**
     * Get movement history for an item
     */
    public function getMovementHistory(int $itemId, ?int $variantId = null, ?int $locationId = null, int $limit = 50): Collection;
    
    /**
     * Adjust stock level
     */
    public function adjustStock(int $itemId, float $quantity, string $reason, array $metadata = []): InventoryMovementData;
    
    /**
     * Reserve stock for an order
     */
    public function reserveStock(int $itemId, float $quantity, string $referenceType, string $referenceId, ?int $variantId = null, ?int $locationId = null): bool;
    
    /**
     * Release reserved stock
     */
    public function releaseReservedStock(string $referenceType, string $referenceId): bool;
    
    /**
     * Commit reserved stock (convert to actual sale)
     */
    public function commitReservedStock(string $referenceType, string $referenceId): bool;
    
    /**
     * Transfer stock between locations
     */
    public function transferStock(int $itemId, int $fromLocationId, int $toLocationId, float $quantity, ?int $variantId = null): bool;
    
    /**
     * Get items below reorder point
     */
    public function getItemsBelowReorderPoint(?int $locationId = null): Collection;
    
    /**
     * Get items out of stock
     */
    public function getOutOfStockItems(?int $locationId = null): Collection;
    
    /**
     * Update reorder levels
     */
    public function updateReorderLevels(int $itemId, float $reorderPoint, float $reorderQuantity, ?int $variantId = null, ?int $locationId = null): bool;
    
    /**
     * Perform stock take (physical count)
     */
    public function performStockTake(array $counts, int $locationId, ?int $userId = null): Collection;
    
    /**
     * Get stock valuation
     */
    public function getStockValuation(?int $locationId = null): float;
    
    /**
     * Get stock turnover report
     */
    public function getStockTurnover(int $days = 30, ?int $locationId = null): Collection;
    
    /**
     * Get slow-moving items
     */
    public function getSlowMovingItems(int $days = 90, ?int $locationId = null): Collection;
    
    /**
     * Calculate reorder suggestions
     */
    public function calculateReorderSuggestions(?int $locationId = null): Collection;
}