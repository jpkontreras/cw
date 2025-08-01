<?php

namespace Colame\Item\Services;

use App\Core\Services\BaseService;
use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Contracts\ResourceMetadataInterface;
use App\Core\Data\PaginatedResourceData;
use App\Core\Data\ResourceMetadata;
use App\Core\Data\ColumnMetadata;
use App\Core\Data\FilterMetadata;
use App\Core\Data\FilterPresetData;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Data\InventoryData;
use Colame\Item\Data\InventoryAdjustmentData;
use Colame\Item\Data\InventoryTransferData;
use Colame\Item\Data\InventoryMovementData;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\StockAlertData;
use Colame\Item\Models\Item;
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

class InventoryService extends BaseService implements ResourceMetadataInterface
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        FeatureFlagInterface $features,
    ) {
        parent::__construct($features);
    }
    
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
        
        $stockLevel = $this->inventoryRepository->getStockLevel($itemId, $variantId, $locationId);
        $availableStock = $this->inventoryRepository->getAvailableStock($itemId, $variantId, $locationId);
        
        // Create InventoryData from the stock information
        return new InventoryData(
            id: null,
            itemId: $itemId,
            variantId: $variantId,
            locationId: $locationId,
            quantityOnHand: $stockLevel,
            quantityReserved: $stockLevel - $availableStock,
            quantityAvailable: $availableStock,
            minQuantity: $item->lowStockThreshold ?? 0,
            reorderQuantity: 0,
            maxQuantity: null,
            unitCost: $item->basePrice,
            lastRestockedAt: null,
            lastCountedAt: null,
        );
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
        if (!$item->trackInventory) {
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
        
        if (!$item->trackInventory) {
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
            
            $metadata = [
                'location_id' => $data['location_id'] ?? null,
                'variant_id' => $data['variant_id'] ?? null,
                'reference_type' => $data['adjustment_type'] ?? 'manual',
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ];
            
            $movement = $this->inventoryRepository->adjustStock(
                $data['item_id'],
                $data['quantity_change'],
                $data['reason'],
                $metadata
            );
            
            // Convert movement data to adjustment data
            $adjustment = new InventoryAdjustmentData(
                id: $movement->id,
                itemId: $data['item_id'],
                variantId: $data['variant_id'] ?? null,
                locationId: $data['location_id'] ?? null,
                quantityChange: $data['quantity_change'],
                adjustmentType: $data['adjustment_type'],
                reason: $data['reason'],
                notes: $data['notes'] ?? null,
                beforeQuantity: $previousQuantity,
                afterQuantity: $newQuantity,
                userId: auth()->id(),
                createdAt: now(),
            );
            
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
            
            $transferId = uniqid('transfer_');
            $success = $this->inventoryRepository->transferStock(
                $data['item_id'],
                $data['from_location_id'],
                $data['to_location_id'],
                $data['quantity'],
                $data['variant_id'] ?? null
            );
            
            if (!$success) {
                throw new InvalidInventoryOperationException('Transfer failed');
            }
            
            // Create transfer data object
            $transfer = new InventoryTransferData(
                id: null,
                itemId: $data['item_id'],
                variantId: $data['variant_id'] ?? null,
                fromLocationId: $data['from_location_id'],
                toLocationId: $data['to_location_id'],
                quantity: $data['quantity'],
                notes: $data['notes'] ?? null,
                status: 'completed',
                transferId: $transferId,
                initiatedBy: auth()->id(),
                completedBy: auth()->id(),
                initiatedAt: now(),
                completedAt: now(),
            );
            
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
        return $this->itemRepository->getLowStockItems($locationId);
    }
    
    /**
     * Get items to reorder
     */
    public function getItemsToReorder(?int $locationId = null): Collection
    {
        if (!$this->features->isEnabled('item.auto_reorder')) {
            return collect();
        }
        
        // Get items that need reordering based on current stock levels
        return $this->itemRepository->getLowStockItems($locationId)
            ->filter(function ($item) use ($locationId) {
                $inventory = $this->getInventoryLevel($item->id, null, $locationId);
                return $inventory && $inventory->quantityOnHand <= $inventory->minQuantity;
            });
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
        
        $success = $this->inventoryRepository->updateReorderLevels(
            $itemId,
            $minQuantity, // This is reorderPoint in the repository
            $reorderQuantity,
            $variantId,
            $locationId
        );
        
        if (!$success) {
            throw new InvalidInventoryOperationException('Failed to update reorder levels');
        }
        
        // Return updated inventory data
        return $this->getInventoryLevel($itemId, $variantId, $locationId);
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
        // Get all items that track inventory
        $items = $this->itemRepository->getItemsWithStock($locationId);
        
        $totalValue = 0;
        $totalItems = 0;
        $valueByCategory = [];
        $itemCount = 0;
        
        foreach ($items as $itemData) {
            $inventory = $this->getInventoryLevel($itemData->id, null, $locationId);
            if ($inventory && $inventory->quantityOnHand > 0) {
                $unitCost = $inventory->unitCost ?? $itemData->basePrice;
                $value = $inventory->quantityOnHand * $unitCost;
                $totalValue += $value;
                $totalItems += $inventory->quantityOnHand;
                $itemCount++;
                
                // Group by category if available
                if (!empty($itemData->categories)) {
                    // For now, use first category
                    $categoryId = $itemData->categories[0] ?? 'Uncategorized';
                    if (!isset($valueByCategory[$categoryId])) {
                        $valueByCategory[$categoryId] = 0;
                    }
                    $valueByCategory[$categoryId] += $value;
                }
            }
        }
        
        return [
            'total_value' => $totalValue,
            'total_items' => $totalItems,
            'value_by_category' => $valueByCategory,
            'item_count' => $itemCount,
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
     * Get paginated inventory data
     */
    public function getPaginatedInventory(array $filters = [], int $perPage = 20, ?int $locationId = null): PaginatedResourceData
    {
        // Add location filter if provided
        if ($locationId !== null) {
            $filters['location_id'] = $locationId;
        }
        
        $paginator = $this->inventoryRepository->paginateWithFilters($filters, $perPage);
        $metadata = $this->getResourceMetadata()->toArray();
        
        return PaginatedResourceData::fromPaginator(
            $paginator,
            InventoryMovementData::class,
            $metadata
        );
    }
    
    /**
     * Get recent inventory adjustments
     */
    public function getRecentAdjustments(int $limit = 10, ?int $locationId = null): Collection
    {
        // Get all recent movements, we'll filter for adjustments
        $movements = DB::table('inventory_movements')
            ->join('items', function($join) {
                $join->on('inventory_movements.inventoriable_id', '=', 'items.id')
                    ->where('inventory_movements.inventoriable_type', '=', Item::class);
            })
            ->leftJoin('users', 'inventory_movements.user_id', '=', 'users.id')
            ->where('inventory_movements.movement_type', 'adjustment')
            ->when($locationId, fn($q) => $q->where('inventory_movements.location_id', $locationId))
            ->select([
                'inventory_movements.id',
                'items.name as item_name',
                'inventory_movements.quantity as quantity_change',
                'inventory_movements.movement_type as adjustment_type',
                'inventory_movements.reason',
                'users.name as adjusted_by',
                'inventory_movements.created_at as adjusted_at',
            ])
            ->orderBy('inventory_movements.created_at', 'desc')
            ->limit($limit)
            ->get();
            
        return $movements;
    }
    
    /**
     * Get pending stock transfers
     */
    public function getPendingTransfers(?int $locationId = null): Collection
    {
        if (!$this->features->isEnabled('item.stock_transfers')) {
            return collect();
        }
        
        // Get transfers that are pending (transfer_out without matching transfer_in)
        $transfers = DB::table('inventory_movements as m1')
            ->leftJoin('inventory_movements as m2', function($join) {
                $join->on('m1.reference_id', '=', 'm2.reference_id')
                     ->where('m2.movement_type', '=', 'transfer_in');
            })
            ->leftJoin('items', function($join) {
                $join->on('m1.inventoriable_id', '=', 'items.id')
                     ->where('m1.inventoriable_type', '=', Item::class);
            })
            ->where('m1.movement_type', 'transfer_out')
            ->whereNull('m2.id') // No matching transfer_in
            ->when($locationId, fn($q) => $q->where('m1.location_id', $locationId))
            ->select([
                'm1.id',
                'items.name as item_name',
                'm1.quantity',
                'm1.location_id as from_location_id',
                'm1.reason',
                'm1.created_at',
            ])
            ->orderBy('m1.created_at', 'desc')
            ->get();
            
        return $transfers;
    }
    
    /**
     * Get recent stock transfers
     */
    public function getRecentTransfers(int $limit = 10, ?int $locationId = null): Collection
    {
        if (!$this->features->isEnabled('item.stock_transfers')) {
            return collect();
        }
        
        // Get recent transfer movements
        $transfers = DB::table('inventory_movements')
            ->leftJoin('items', function($join) {
                $join->on('inventory_movements.inventoriable_id', '=', 'items.id')
                     ->where('inventory_movements.inventoriable_type', '=', Item::class);
            })
            ->whereIn('inventory_movements.movement_type', ['transfer_in', 'transfer_out'])
            ->when($locationId, fn($q) => $q->where('inventory_movements.location_id', $locationId))
            ->select([
                'inventory_movements.id',
                'items.name as item_name',
                'inventory_movements.quantity',
                'inventory_movements.movement_type',
                'inventory_movements.location_id',
                'inventory_movements.reason',
                'inventory_movements.created_at',
                'inventory_movements.reference_id',
            ])
            ->orderBy('inventory_movements.created_at', 'desc')
            ->limit($limit)
            ->get();
            
        return $transfers;
    }
    
    /**
     * Get last stock take information
     */
    public function getLastStockTake(?int $locationId = null): ?array
    {
        // Get the most recent stock take adjustment
        $lastStockTake = DB::table('inventory_movements')
            ->leftJoin('users', 'inventory_movements.user_id', '=', 'users.id')
            ->where('inventory_movements.reference_type', 'stock_take')
            ->when($locationId, fn($q) => $q->where('inventory_movements.location_id', $locationId))
            ->select([
                'inventory_movements.created_at',
                'inventory_movements.location_id',
                'users.name as user_name',
                DB::raw('COUNT(DISTINCT inventory_movements.inventoriable_id) as items_counted'),
                DB::raw('COUNT(*) as total_adjustments')
            ])
            ->groupBy('inventory_movements.created_at', 'inventory_movements.location_id', 'users.name')
            ->orderBy('inventory_movements.created_at', 'desc')
            ->first();
        
        if (!$lastStockTake) {
            return null;
        }
        
        return [
            'date' => $lastStockTake->created_at,
            'user' => $lastStockTake->user_name ?? 'System',
            'items_counted' => $lastStockTake->items_counted ?? 0,
            'total_adjustments' => $lastStockTake->total_adjustments ?? 0,
            'location_id' => $lastStockTake->location_id,
        ];
    }
    
    /**
     * Get reorder rules
     */
    public function getReorderRules(?int $locationId = null): Collection
    {
        if (!$this->features->isEnabled('item.auto_reorder')) {
            return collect();
        }
        
        // Get items with reorder settings
        if ($locationId) {
            $rules = DB::table('item_location_stock')
                ->join('items', 'items.id', '=', 'item_location_stock.item_id')
                ->where('item_location_stock.location_id', $locationId)
                ->where('item_location_stock.reorder_point', '>', 0)
                ->select([
                    'item_location_stock.item_id',
                    'items.name as item_name',
                    'item_location_stock.reorder_point',
                    'item_location_stock.reorder_quantity',
                    'item_location_stock.quantity as current_stock',
                ])
                ->get();
        } else {
            $rules = DB::table('items')
                ->where('track_inventory', true)
                ->where('low_stock_threshold', '>', 0)
                ->select([
                    'id as item_id',
                    'name as item_name',
                    'low_stock_threshold as reorder_point',
                    DB::raw('0 as reorder_quantity'),
                    'stock_quantity as current_stock',
                ])
                ->get();
        }
        
        return $rules;
    }
    
    /**
     * Get current stock level
     */
    public function getStockLevel(int $itemId, ?int $variantId = null, ?int $locationId = null): float
    {
        $inventory = $this->getInventoryLevel($itemId, $variantId, $locationId);
        return $inventory ? $inventory->quantityOnHand : 0;
    }
    
    /**
     * Get available stock (considering reservations)
     */
    public function getAvailableStock(int $itemId, ?int $variantId = null, ?int $locationId = null): float
    {
        $inventory = $this->getInventoryLevel($itemId, $variantId, $locationId);
        if (!$inventory) {
            return 0;
        }
        
        if ($this->features->isEnabled('item.stock_reservation')) {
            return $inventory->quantityOnHand - $inventory->quantityReserved;
        }
        
        return $inventory->quantityOnHand;
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
    
    /**
     * Get resource metadata for inventory listing
     */
    public function getResourceMetadata(array $context = []): ResourceMetadata
    {
        $columns = [];
        
        // Define columns for inventory movements
        $columns['item_name'] = ColumnMetadata::text('item_name', 'Item');
            
        $columns['movement_type'] = ColumnMetadata::enum('movement_type', 'Type', $this->inventoryRepository->getFilterOptions('movement_type'))
            ->withFilter(FilterMetadata::multiSelect(
                'movement_type',
                'Movement Type',
                $this->inventoryRepository->getFilterOptions('movement_type')
            ));
        
        $columns['quantity'] = ColumnMetadata::number('quantity', 'Quantity');
            
        $columns['before_quantity'] = ColumnMetadata::number('before_quantity', 'Before');
        $columns['after_quantity'] = ColumnMetadata::number('after_quantity', 'After');
        
        $columns['created_at'] = ColumnMetadata::datetime('created_at', 'Date')
            ->withFilter(FilterMetadata::dateRange('created_at', 'Date Range'));
            
        $columns['user_name'] = ColumnMetadata::text('user_name', 'User');
        
        if ($this->features->isEnabled('item.multi_location')) {
            $columns['location_name'] = ColumnMetadata::text('location_name', 'Location')
                ->withFilter(FilterMetadata::select(
                    'location_id',
                    'Location',
                    $this->inventoryRepository->getFilterOptions('location_id')
                ));
        }
        
        return new ResourceMetadata(
            columns: ColumnMetadata::collect($columns, \Spatie\LaravelData\DataCollection::class),
            defaultFilters: ['search', 'movement_type', 'created_at'],
            defaultSort: '-created_at',
            perPageOptions: [20, 50, 100],
            defaultPerPage: 20,
        );
    }
    
    /**
     * Get filter presets for inventory
     */
    public function getFilterPresets(): array
    {
        return [
            new FilterPresetData(
                id: 'adjustments',
                name: 'Adjustments',
                description: 'Manual inventory adjustments',
                filters: [
                    'movement_type' => ['adjustment'],
                ],
                icon: 'edit'
            ),
            new FilterPresetData(
                id: 'sales',
                name: 'Sales',
                description: 'Inventory movements from sales',
                filters: [
                    'movement_type' => ['sale'],
                ],
                icon: 'shopping-cart'
            ),
            new FilterPresetData(
                id: 'transfers',
                name: 'Transfers',
                description: 'Stock transfers between locations',
                filters: [
                    'movement_type' => ['transfer_in', 'transfer_out'],
                ],
                icon: 'arrow-right-left'
            ),
            new FilterPresetData(
                id: 'today',
                name: 'Today\'s Movements',
                description: 'All movements from today',
                filters: [
                    'created_at' => [
                        'start' => now()->startOfDay()->toISOString(),
                        'end' => now()->endOfDay()->toISOString(),
                    ],
                ],
                icon: 'calendar'
            ),
        ];
    }
    
    /**
     * Get available actions for inventory
     */
    public function getAvailableActions(array $context = []): array
    {
        $actions = [
            [
                'id' => 'view',
                'label' => 'View Details',
                'icon' => 'eye',
                'route' => 'inventory.show',
            ],
        ];
        
        if ($this->features->isEnabled('item.inventory_adjustments')) {
            $actions[] = [
                'id' => 'adjust',
                'label' => 'Adjust Stock',
                'icon' => 'edit',
                'route' => 'inventory.adjust',
            ];
        }
        
        if ($this->features->isEnabled('item.stock_transfers')) {
            $actions[] = [
                'id' => 'transfer',
                'label' => 'Transfer Stock',
                'icon' => 'arrow-right-left',
                'route' => 'inventory.transfer',
            ];
        }
        
        return $actions;
    }
    
    /**
     * Get export configuration for inventory
     */
    public function getExportConfiguration(): array
    {
        return [
            'formats' => [
                'csv' => [
                    'label' => 'CSV',
                    'extension' => 'csv',
                    'mimeType' => 'text/csv',
                ],
                'excel' => [
                    'label' => 'Excel',
                    'extension' => 'xlsx',
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                'pdf' => [
                    'label' => 'PDF',
                    'extension' => 'pdf',
                    'mimeType' => 'application/pdf',
                ],
            ],
            'defaultFormat' => 'csv',
            'defaultFields' => [
                'item_name',
                'movement_type',
                'quantity',
                'before_quantity',
                'after_quantity',
                'created_at',
                'user_name',
            ],
            'availableFields' => [
                'item_name' => 'Item',
                'movement_type' => 'Type',
                'quantity' => 'Quantity',
                'before_quantity' => 'Before',
                'after_quantity' => 'After',
                'unit_cost' => 'Unit Cost',
                'total_cost' => 'Total Cost',
                'reference_type' => 'Reference Type',
                'reference_id' => 'Reference ID',
                'reason' => 'Reason',
                'created_at' => 'Date',
                'user_name' => 'User',
                'location_name' => 'Location',
            ],
        ];
    }
    
    /**
     * Export inventory data
     */
    public function exportInventory(?int $locationId = null, string $format = 'csv'): array
    {
        // Get inventory data
        $inventory = DB::table('inventory_movements')
            ->join('items', function($join) {
                $join->on('inventory_movements.inventoriable_id', '=', 'items.id')
                    ->where('inventory_movements.inventoriable_type', '=', Item::class);
            })
            ->leftJoin('users', 'inventory_movements.user_id', '=', 'users.id')
            ->when($locationId, fn($q) => $q->where('inventory_movements.location_id', $locationId))
            ->select([
                'items.name as item_name',
                'items.sku',
                'inventory_movements.movement_type',
                'inventory_movements.quantity',
                'inventory_movements.before_quantity',
                'inventory_movements.after_quantity',
                'inventory_movements.unit_cost',
                'inventory_movements.reason',
                'inventory_movements.created_at',
                'users.name as user_name',
            ])
            ->orderBy('inventory_movements.created_at', 'desc')
            ->get();
        
        // Generate export file
        $filename = 'inventory_export_' . now()->format('Y-m-d_His') . '.' . $format;
        $path = storage_path('app/exports/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }
        
        if ($format === 'csv') {
            $handle = fopen($path, 'w');
            
            // Write headers
            fputcsv($handle, [
                'Item Name',
                'SKU',
                'Movement Type',
                'Quantity',
                'Before Quantity',
                'After Quantity',
                'Unit Cost',
                'Reason',
                'Date',
                'User',
            ]);
            
            // Write data
            foreach ($inventory as $movement) {
                fputcsv($handle, [
                    $movement->item_name,
                    $movement->sku ?? '',
                    $movement->movement_type,
                    $movement->quantity,
                    $movement->before_quantity,
                    $movement->after_quantity,
                    $movement->unit_cost ?? '',
                    $movement->reason ?? '',
                    $movement->created_at,
                    $movement->user_name ?? 'System',
                ]);
            }
            
            fclose($handle);
        }
        
        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }
}