<?php

namespace Colame\Item\Services;

use App\Core\Services\BaseService;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Data\InventoryData;
use Colame\Item\Data\InventoryAdjustmentData;
use Colame\Item\Data\InventoryTransferData;
use Colame\Item\Data\StockAlertData;
use Colame\Item\Exceptions\InsufficientStockException;
use Colame\Item\Exceptions\InvalidInventoryOperationException;
use Colame\Item\Exceptions\ItemNotFoundException;
use Colame\Item\Events\StockAdjusted;
use Colame\Item\Events\StockTransferred;
use Colame\Item\Events\LowStockAlert;
use Colame\Item\Events\StockReplenished;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InventoryService extends BaseService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Get current inventory levels
     */
    public function getInventoryLevel(
        int $itemId,
        ?int $variantId = null,
        ?int $locationId = null
    ): ?InventoryData {
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        return $this->inventoryRepository->getInventoryLevel($itemId, $variantId, $locationId);
    }
    
    /**
     * Check stock availability
     */
    public function checkAvailability(
        int $itemId,
        float $quantity,
        ?int $variantId = null,
        ?int $locationId = null
    ): bool {
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        // If item doesn't track stock, it's always available
        if (!$item->trackStock) {
            return true;
        }
        
        $inventory = $this->getInventoryLevel($itemId, $variantId, $locationId);
        if (!$inventory) {
            return false;
        }
        
        // Consider reserved quantity if feature is enabled
        if ($this->features->isEnabled('item.stock_reservation')) {
            $availableQuantity = $inventory->quantityOnHand - $inventory->quantityReserved;
        } else {
            $availableQuantity = $inventory->quantityOnHand;
        }
        
        return $availableQuantity >= $quantity;
    }
    
    /**
     * Adjust inventory level
     */
    public function adjustInventory(array $data): InventoryAdjustmentData
    {
        $this->authorize('item.inventory.adjust');
        
        $item = $this->itemRepository->find($data['item_id']);
        if (!$item) {
            throw new ItemNotFoundException($data['item_id']);
        }
        
        if (!$item->trackStock) {
            throw new InvalidInventoryOperationException('Item does not track stock');
        }
        
        DB::beginTransaction();
        try {
            $currentInventory = $this->getInventoryLevel(
                $data['item_id'],
                $data['variant_id'] ?? null,
                $data['location_id'] ?? null
            );
            
            $previousQuantity = $currentInventory ? $currentInventory->quantityOnHand : 0;
            $newQuantity = $previousQuantity + $data['quantity_change'];
            
            if ($newQuantity < 0) {
                throw new InsufficientStockException(
                    $item->name,
                    abs($data['quantity_change']),
                    $previousQuantity
                );
            }
            
            $adjustment = $this->inventoryRepository->adjustInventory($data);
            
            // Check for low stock alert
            $this->checkLowStockAlert($data['item_id'], $data['variant_id'] ?? null, $data['location_id'] ?? null);
            
            DB::commit();
            
            event(new StockAdjusted($adjustment));
            
            return $adjustment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to adjust inventory', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Reserve stock for order
     */
    public function reserveStock(
        int $itemId,
        float $quantity,
        ?int $variantId = null,
        ?int $locationId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): bool {
        if (!$this->features->isEnabled('item.stock_reservation')) {
            return true;
        }
        
        if (!$this->checkAvailability($itemId, $quantity, $variantId, $locationId)) {
            throw new InsufficientStockException();
        }
        
        DB::beginTransaction();
        try {
            $reserved = $this->inventoryRepository->reserveStock(
                $itemId,
                $quantity,
                $variantId,
                $locationId,
                $referenceType,
                $referenceId
            );
            
            DB::commit();
            
            return $reserved;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reserve stock', [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Release reserved stock
     */
    public function releaseReservedStock(
        int $itemId,
        float $quantity,
        ?int $variantId = null,
        ?int $locationId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): bool {
        if (!$this->features->isEnabled('item.stock_reservation')) {
            return true;
        }
        
        DB::beginTransaction();
        try {
            $released = $this->inventoryRepository->releaseReservedStock(
                $itemId,
                $quantity,
                $variantId,
                $locationId,
                $referenceType,
                $referenceId
            );
            
            DB::commit();
            
            return $released;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to release reserved stock', [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Transfer stock between locations
     */
    public function transferStock(array $data): InventoryTransferData
    {
        $this->authorize('item.inventory.transfer');
        
        if (!$this->features->isEnabled('item.stock_transfers')) {
            throw new InvalidInventoryOperationException('Stock transfers are not enabled');
        }
        
        $item = $this->itemRepository->find($data['item_id']);
        if (!$item) {
            throw new ItemNotFoundException($data['item_id']);
        }
        
        if ($data['from_location_id'] == $data['to_location_id']) {
            throw new InvalidInventoryOperationException('Cannot transfer to the same location');
        }
        
        DB::beginTransaction();
        try {
            // Check source availability
            if (!$this->checkAvailability(
                $data['item_id'],
                $data['quantity'],
                $data['variant_id'] ?? null,
                $data['from_location_id']
            )) {
                throw new InsufficientStockException();
            }
            
            $transfer = $this->inventoryRepository->transferStock($data);
            
            // Check low stock at source location
            $this->checkLowStockAlert(
                $data['item_id'],
                $data['variant_id'] ?? null,
                $data['from_location_id']
            );
            
            DB::commit();
            
            event(new StockTransferred($transfer));
            
            return $transfer;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to transfer stock', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get items with low stock
     */
    public function getLowStockItems(?int $locationId = null): Collection
    {
        return $this->inventoryRepository->getLowStockItems($locationId);
    }
    
    /**
     * Get items to reorder
     */
    public function getItemsToReorder(?int $locationId = null): Collection
    {
        if (!$this->features->isEnabled('item.auto_reorder')) {
            return collect();
        }
        
        return $this->inventoryRepository->getItemsToReorder($locationId);
    }
    
    /**
     * Update reorder levels
     */
    public function updateReorderLevels(
        int $itemId,
        ?int $variantId,
        ?int $locationId,
        float $minQuantity,
        float $reorderQuantity,
        ?float $maxQuantity = null
    ): InventoryData {
        $this->authorize('item.inventory.manage');
        
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        if ($minQuantity < 0 || $reorderQuantity <= 0) {
            throw new InvalidInventoryOperationException('Invalid reorder levels');
        }
        
        if ($maxQuantity !== null && $maxQuantity < $reorderQuantity) {
            throw new InvalidInventoryOperationException('Max quantity must be greater than reorder quantity');
        }
        
        return $this->inventoryRepository->updateReorderLevels(
            $itemId,
            $variantId,
            $locationId,
            $minQuantity,
            $reorderQuantity,
            $maxQuantity
        );
    }
    
    /**
     * Get inventory movement history
     */
    public function getMovementHistory(
        int $itemId,
        ?int $variantId = null,
        ?int $locationId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        return $this->inventoryRepository->getMovementHistory(
            $itemId,
            $variantId,
            $locationId,
            $startDate,
            $endDate
        );
    }
    
    /**
     * Calculate inventory value
     */
    public function calculateInventoryValue(?int $locationId = null): array
    {
        $inventory = $this->inventoryRepository->getAllInventory($locationId);
        
        $totalValue = 0;
        $totalItems = 0;
        $valueByCategory = [];
        
        foreach ($inventory as $item) {
            $value = $item->quantityOnHand * $item->unitCost;
            $totalValue += $value;
            $totalItems += $item->quantityOnHand;
            
            // Group by category if available
            if ($item->categoryName) {
                if (!isset($valueByCategory[$item->categoryName])) {
                    $valueByCategory[$item->categoryName] = 0;
                }
                $valueByCategory[$item->categoryName] += $value;
            }
        }
        
        return [
            'total_value' => $totalValue,
            'total_items' => $totalItems,
            'value_by_category' => $valueByCategory,
            'item_count' => $inventory->count(),
            'calculated_at' => now()
        ];
    }
    
    /**
     * Perform stock take (inventory count)
     */
    public function performStockTake(array $counts): array
    {
        $this->authorize('item.inventory.stock_take');
        
        $results = [
            'adjusted' => 0,
            'errors' => [],
            'adjustments' => []
        ];
        
        DB::beginTransaction();
        try {
            foreach ($counts as $count) {
                try {
                    $current = $this->getInventoryLevel(
                        $count['item_id'],
                        $count['variant_id'] ?? null,
                        $count['location_id'] ?? null
                    );
                    
                    $currentQuantity = $current ? $current->quantityOnHand : 0;
                    $difference = $count['counted_quantity'] - $currentQuantity;
                    
                    if ($difference != 0) {
                        $adjustment = $this->adjustInventory([
                            'item_id' => $count['item_id'],
                            'variant_id' => $count['variant_id'] ?? null,
                            'location_id' => $count['location_id'] ?? null,
                            'quantity_change' => $difference,
                            'adjustment_type' => 'stock_take',
                            'reason' => 'Stock take adjustment',
                            'notes' => $count['notes'] ?? null
                        ]);
                        
                        $results['adjustments'][] = $adjustment;
                        $results['adjusted']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'item_id' => $count['item_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * Check and trigger low stock alert
     */
    private function checkLowStockAlert(int $itemId, ?int $variantId, ?int $locationId): void
    {
        $inventory = $this->getInventoryLevel($itemId, $variantId, $locationId);
        if (!$inventory) {
            return;
        }
        
        $item = $this->itemRepository->find($itemId);
        
        // Check if below minimum
        if ($inventory->quantityOnHand <= $inventory->minQuantity) {
            event(new LowStockAlert(new StockAlertData(
                itemId: $itemId,
                variantId: $variantId,
                locationId: $locationId,
                itemName: $item->name,
                currentQuantity: $inventory->quantityOnHand,
                minQuantity: $inventory->minQuantity,
                alertType: 'low_stock'
            )));
        }
        
        // Check if replenished above minimum (was low, now ok)
        if ($inventory->quantityOnHand > $inventory->minQuantity) {
            // Check previous state would be needed here
            event(new StockReplenished(new StockAlertData(
                itemId: $itemId,
                variantId: $variantId,
                locationId: $locationId,
                itemName: $item->name,
                currentQuantity: $inventory->quantityOnHand,
                minQuantity: $inventory->minQuantity,
                alertType: 'replenished'
            )));
        }
    }
}