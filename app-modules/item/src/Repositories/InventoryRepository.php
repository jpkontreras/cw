<?php

namespace Colame\Item\Repositories;

use App\Core\Traits\ValidatesPagination;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use Colame\Item\Data\InventoryMovementData;
use Colame\Item\Data\ItemLocationStockData;
use Colame\Item\Models\InventoryMovement;
use Colame\Item\Models\ItemLocationStock;
use Colame\Item\Models\Item;
use Colame\Item\Models\ItemVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryRepository implements InventoryRepositoryInterface
{
    use ValidatesPagination;
    
    /**
     * Get current stock level for an item
     */
    public function getStockLevel(int $itemId, ?int $variantId = null, ?int $locationId = null): float
    {
        if ($locationId) {
            $stock = ItemLocationStock::where('item_id', $itemId)
                ->where('item_variant_id', $variantId)
                ->where('location_id', $locationId)
                ->first();
            
            return $stock ? $stock->quantity : 0;
        }
        
        if ($variantId) {
            $variant = ItemVariant::find($variantId);
            return $variant && $variant->item_id === $itemId ? $variant->stock_quantity : 0;
        }
        
        $item = Item::find($itemId);
        return $item ? $item->stock_quantity : 0;
    }
    
    /**
     * Get available stock (quantity minus reserved)
     */
    public function getAvailableStock(int $itemId, ?int $variantId = null, ?int $locationId = null): float
    {
        if ($locationId) {
            $stock = ItemLocationStock::where('item_id', $itemId)
                ->where('item_variant_id', $variantId)
                ->where('location_id', $locationId)
                ->first();
            
            return $stock ? $stock->available_quantity : 0;
        }
        
        // For non-location stock, we don't track reservations in the base implementation
        return $this->getStockLevel($itemId, $variantId, $locationId);
    }
    
    /**
     * Get stock levels across all locations
     */
    public function getStockByLocations(int $itemId, ?int $variantId = null): Collection
    {
        return ItemLocationStock::where('item_id', $itemId)
            ->where('item_variant_id', $variantId)
            ->get()
            ->map(fn($stock) => ItemLocationStockData::from($stock));
    }
    
    /**
     * Create a stock movement record
     */
    public function createMovement(array $data): InventoryMovementData
    {
        $movement = InventoryMovement::create($data);
        
        return InventoryMovementData::from($movement);
    }
    
    /**
     * Get movement history for an item
     */
    public function getMovementHistory(int $itemId, ?int $variantId = null, ?int $locationId = null, int $limit = 50): Collection
    {
        $query = InventoryMovement::query();
        
        if ($variantId) {
            $query->where('inventoriable_type', ItemVariant::class)
                ->where('inventoriable_id', $variantId);
        } else {
            $query->where('inventoriable_type', Item::class)
                ->where('inventoriable_id', $itemId);
        }
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($movement) => InventoryMovementData::from($movement));
    }
    
    /**
     * Adjust stock level
     */
    public function adjustStock(int $itemId, float $quantity, string $reason, array $metadata = []): InventoryMovementData
    {
        $item = Item::findOrFail($itemId);
        $beforeQuantity = $item->stock_quantity;
        $afterQuantity = $beforeQuantity + $quantity;
        
        DB::transaction(function () use ($item, $afterQuantity) {
            $item->stock_quantity = $afterQuantity;
            $item->save();
        });
        
        $movementData = [
            'inventoriable_type' => Item::class,
            'inventoriable_id' => $itemId,
            'location_id' => $metadata['location_id'] ?? null,
            'movement_type' => 'adjustment',
            'quantity' => $quantity,
            'unit_cost' => $metadata['unit_cost'] ?? null,
            'before_quantity' => $beforeQuantity,
            'after_quantity' => $afterQuantity,
            'reference_type' => $metadata['reference_type'] ?? null,
            'reference_id' => $metadata['reference_id'] ?? null,
            'reason' => $reason,
            'user_id' => $metadata['user_id'] ?? null,
        ];
        
        return $this->createMovement($movementData);
    }
    
    /**
     * Reserve stock for an order
     */
    public function reserveStock(int $itemId, float $quantity, string $referenceType, string $referenceId, ?int $variantId = null, ?int $locationId = null): bool
    {
        if (!$locationId) {
            // Base implementation doesn't track reservations without location
            return true;
        }
        
        $stock = ItemLocationStock::firstOrCreate([
            'item_id' => $itemId,
            'item_variant_id' => $variantId,
            'location_id' => $locationId,
        ]);
        
        // Check if enough available stock
        if ($stock->available_quantity < $quantity) {
            return false;
        }
        
        // Increase reserved quantity
        $stock->reserved_quantity += $quantity;
        return $stock->save();
    }
    
    /**
     * Release reserved stock
     */
    public function releaseReservedStock(string $referenceType, string $referenceId): bool
    {
        // Find all movements for this reference
        $movements = InventoryMovement::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('movement_type', 'reservation')
            ->get();
        
        foreach ($movements as $movement) {
            if ($movement->location_id) {
                $stock = ItemLocationStock::where('item_id', $movement->inventoriable_id)
                    ->where('location_id', $movement->location_id)
                    ->first();
                
                if ($stock) {
                    $stock->reserved_quantity = max(0, $stock->reserved_quantity - abs($movement->quantity));
                    $stock->save();
                }
            }
        }
        
        return true;
    }
    
    /**
     * Commit reserved stock (convert to actual sale)
     */
    public function commitReservedStock(string $referenceType, string $referenceId): bool
    {
        // Find all reservation movements for this reference
        $movements = InventoryMovement::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('movement_type', 'reservation')
            ->get();
        
        DB::transaction(function () use ($movements, $referenceType, $referenceId) {
            foreach ($movements as $movement) {
                if ($movement->location_id) {
                    $stock = ItemLocationStock::where('item_id', $movement->inventoriable_id)
                        ->where('location_id', $movement->location_id)
                        ->first();
                    
                    if ($stock) {
                        $quantity = abs($movement->quantity);
                        $stock->reserved_quantity = max(0, $stock->reserved_quantity - $quantity);
                        $stock->quantity = max(0, $stock->quantity - $quantity);
                        $stock->save();
                        
                        // Create sale movement
                        $this->createMovement([
                            'inventoriable_type' => $movement->inventoriable_type,
                            'inventoriable_id' => $movement->inventoriable_id,
                            'location_id' => $movement->location_id,
                            'movement_type' => 'sale',
                            'quantity' => -$quantity,
                            'before_quantity' => $stock->quantity + $quantity,
                            'after_quantity' => $stock->quantity,
                            'reference_type' => $referenceType,
                            'reference_id' => $referenceId,
                            'user_id' => $movement->user_id,
                        ]);
                    }
                }
            }
        });
        
        return true;
    }
    
    /**
     * Transfer stock between locations
     */
    public function transferStock(int $itemId, int $fromLocationId, int $toLocationId, float $quantity, ?int $variantId = null): bool
    {
        return DB::transaction(function () use ($itemId, $fromLocationId, $toLocationId, $quantity, $variantId) {
            // Get source stock
            $fromStock = ItemLocationStock::where('item_id', $itemId)
                ->where('item_variant_id', $variantId)
                ->where('location_id', $fromLocationId)
                ->first();
            
            if (!$fromStock || $fromStock->available_quantity < $quantity) {
                return false;
            }
            
            // Get or create destination stock
            $toStock = ItemLocationStock::firstOrCreate([
                'item_id' => $itemId,
                'item_variant_id' => $variantId,
                'location_id' => $toLocationId,
            ]);
            
            // Update quantities
            $fromStock->quantity -= $quantity;
            $toStock->quantity += $quantity;
            
            $fromStock->save();
            $toStock->save();
            
            // Create movement records
            $transferId = uniqid('transfer_');
            
            $this->createMovement([
                'inventoriable_type' => $variantId ? ItemVariant::class : Item::class,
                'inventoriable_id' => $variantId ?: $itemId,
                'location_id' => $fromLocationId,
                'movement_type' => 'transfer_out',
                'quantity' => -$quantity,
                'before_quantity' => $fromStock->quantity + $quantity,
                'after_quantity' => $fromStock->quantity,
                'reference_type' => 'transfer',
                'reference_id' => $transferId,
                'reason' => "Transfer to location {$toLocationId}",
            ]);
            
            $this->createMovement([
                'inventoriable_type' => $variantId ? ItemVariant::class : Item::class,
                'inventoriable_id' => $variantId ?: $itemId,
                'location_id' => $toLocationId,
                'movement_type' => 'transfer_in',
                'quantity' => $quantity,
                'before_quantity' => $toStock->quantity - $quantity,
                'after_quantity' => $toStock->quantity,
                'reference_type' => 'transfer',
                'reference_id' => $transferId,
                'reason' => "Transfer from location {$fromLocationId}",
            ]);
            
            return true;
        });
    }
    
    /**
     * Get items below reorder point
     */
    public function getItemsBelowReorderPoint(?int $locationId = null): Collection
    {
        if ($locationId) {
            return ItemLocationStock::where('location_id', $locationId)
                ->needsReorder()
                ->get()
                ->map(fn($stock) => ItemLocationStockData::from($stock));
        }
        
        // Get items from main inventory
        $items = Item::tracksInventory()
            ->whereRaw('stock_quantity <= low_stock_threshold')
            ->get();
        
        return $items->map(function ($item) {
            return new ItemLocationStockData(
                id: null,
                itemId: $item->id,
                itemVariantId: null,
                locationId: 0,
                quantity: $item->stock_quantity,
                reservedQuantity: 0,
                reorderPoint: $item->low_stock_threshold,
                reorderQuantity: 0,
            );
        });
    }
    
    /**
     * Get items out of stock
     */
    public function getOutOfStockItems(?int $locationId = null): Collection
    {
        if ($locationId) {
            return ItemLocationStock::where('location_id', $locationId)
                ->outOfStock()
                ->get()
                ->map(fn($stock) => ItemLocationStockData::from($stock));
        }
        
        // Get items from main inventory
        $items = Item::outOfStock()->get();
        
        return $items->map(function ($item) {
            return new ItemLocationStockData(
                id: null,
                itemId: $item->id,
                itemVariantId: null,
                locationId: 0,
                quantity: 0,
                reservedQuantity: 0,
                reorderPoint: $item->low_stock_threshold,
                reorderQuantity: 0,
            );
        });
    }
    
    /**
     * Update reorder levels
     */
    public function updateReorderLevels(int $itemId, float $reorderPoint, float $reorderQuantity, ?int $variantId = null, ?int $locationId = null): bool
    {
        if ($locationId) {
            $stock = ItemLocationStock::firstOrCreate([
                'item_id' => $itemId,
                'item_variant_id' => $variantId,
                'location_id' => $locationId,
            ]);
            
            $stock->reorder_point = $reorderPoint;
            $stock->reorder_quantity = $reorderQuantity;
            return $stock->save();
        }
        
        // Update main item
        $item = Item::find($itemId);
        if ($item) {
            $item->low_stock_threshold = $reorderPoint;
            return $item->save();
        }
        
        return false;
    }
    
    /**
     * Perform stock take (physical count)
     */
    public function performStockTake(array $counts, int $locationId, ?int $userId = null): Collection
    {
        $movements = collect();
        
        DB::transaction(function () use ($counts, $locationId, $userId, &$movements) {
            foreach ($counts as $count) {
                $itemId = $count['item_id'];
                $variantId = $count['variant_id'] ?? null;
                $physicalCount = $count['quantity'];
                
                $stock = ItemLocationStock::firstOrCreate([
                    'item_id' => $itemId,
                    'item_variant_id' => $variantId,
                    'location_id' => $locationId,
                ]);
                
                $systemCount = $stock->quantity;
                $variance = $physicalCount - $systemCount;
                
                if ($variance != 0) {
                    $stock->quantity = $physicalCount;
                    $stock->save();
                    
                    $movement = $this->createMovement([
                        'inventoriable_type' => $variantId ? ItemVariant::class : Item::class,
                        'inventoriable_id' => $variantId ?: $itemId,
                        'location_id' => $locationId,
                        'movement_type' => 'adjustment',
                        'quantity' => $variance,
                        'before_quantity' => $systemCount,
                        'after_quantity' => $physicalCount,
                        'reference_type' => 'stock_take',
                        'reference_id' => date('Y-m-d'),
                        'reason' => sprintf('Stock take variance: %+.2f', $variance),
                        'user_id' => $userId,
                    ]);
                    
                    $movements->push($movement);
                }
            }
        });
        
        return $movements;
    }
    
    /**
     * Get stock valuation
     */
    public function getStockValuation(?int $locationId = null): float
    {
        if ($locationId) {
            return DB::table('item_location_stock')
                ->join('items', 'items.id', '=', 'item_location_stock.item_id')
                ->where('item_location_stock.location_id', $locationId)
                ->where('item_location_stock.quantity', '>', 0)
                ->sum(DB::raw('item_location_stock.quantity * items.base_cost'));
        }
        
        return Item::tracksInventory()
            ->where('stock_quantity', '>', 0)
            ->sum(DB::raw('stock_quantity * base_cost'));
    }
    
    /**
     * Get stock turnover report
     */
    public function getStockTurnover(int $days = 30, ?int $locationId = null): Collection
    {
        $startDate = now()->subDays($days);
        
        $sales = InventoryMovement::where('movement_type', 'sale')
            ->where('created_at', '>=', $startDate)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->select('inventoriable_type', 'inventoriable_id', DB::raw('SUM(ABS(quantity)) as total_sold'))
            ->groupBy('inventoriable_type', 'inventoriable_id')
            ->get();
        
        return $sales->map(function ($sale) use ($locationId) {
            $currentStock = $this->getStockLevel(
                $sale->inventoriable_id,
                $sale->inventoriable_type === ItemVariant::class ? $sale->inventoriable_id : null,
                $locationId
            );
            
            $averageStock = ($currentStock + $sale->total_sold) / 2;
            $turnoverRate = $averageStock > 0 ? $sale->total_sold / $averageStock : 0;
            
            return [
                'item_id' => $sale->inventoriable_id,
                'is_variant' => $sale->inventoriable_type === ItemVariant::class,
                'total_sold' => $sale->total_sold,
                'current_stock' => $currentStock,
                'turnover_rate' => round($turnoverRate, 2),
                'days_of_stock' => $sale->total_sold > 0 ? round($currentStock / ($sale->total_sold / $days), 1) : null,
            ];
        });
    }
    
    /**
     * Get slow-moving items
     */
    public function getSlowMovingItems(int $days = 90, ?int $locationId = null): Collection
    {
        $startDate = now()->subDays($days);
        
        // Get items with stock
        $itemsWithStock = $locationId
            ? ItemLocationStock::where('location_id', $locationId)
                ->where('quantity', '>', 0)
                ->pluck('item_id')
            : Item::tracksInventory()
                ->where('stock_quantity', '>', 0)
                ->pluck('id');
        
        // Get items with low or no sales
        $sales = InventoryMovement::where('movement_type', 'sale')
            ->where('created_at', '>=', $startDate)
            ->whereIn('inventoriable_id', $itemsWithStock)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->select('inventoriable_id', DB::raw('SUM(ABS(quantity)) as total_sold'))
            ->groupBy('inventoriable_id')
            ->having('total_sold', '<', 5) // Less than 5 units sold
            ->pluck('inventoriable_id');
        
        // Get items with no sales at all
        $noSales = $itemsWithStock->diff($sales);
        
        return Item::whereIn('id', $noSales->merge($sales))
            ->get()
            ->map(function ($item) use ($locationId) {
                $stock = $this->getStockLevel($item->id, null, $locationId);
                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'current_stock' => $stock,
                    'stock_value' => $stock * $item->base_cost,
                ];
            });
    }
    
    /**
     * Calculate reorder suggestions
     */
    public function calculateReorderSuggestions(?int $locationId = null): Collection
    {
        $suggestions = collect();
        
        // Get items below reorder point
        $lowStockItems = $this->getItemsBelowReorderPoint($locationId);
        
        foreach ($lowStockItems as $stockData) {
            $item = Item::find($stockData->itemId);
            if (!$item) continue;
            
            // Calculate average daily usage (last 30 days)
            $movements = InventoryMovement::where('inventoriable_type', Item::class)
                ->where('inventoriable_id', $stockData->itemId)
                ->where('movement_type', 'sale')
                ->where('created_at', '>=', now()->subDays(30))
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->sum(DB::raw('ABS(quantity)'));
            
            $avgDailyUsage = $movements / 30;
            $leadTimeDays = 7; // Default lead time
            $safetyStock = $avgDailyUsage * 3; // 3 days safety stock
            
            $suggestedQuantity = max(
                ($avgDailyUsage * $leadTimeDays) + $safetyStock - $stockData->quantity,
                $stockData->reorderQuantity
            );
            
            $suggestions->push([
                'item_id' => $stockData->itemId,
                'item_name' => $item->name,
                'current_stock' => $stockData->quantity,
                'reorder_point' => $stockData->reorderPoint,
                'avg_daily_usage' => round($avgDailyUsage, 2),
                'suggested_quantity' => ceil($suggestedQuantity),
                'estimated_cost' => $item->base_cost * ceil($suggestedQuantity),
            ]);
        }
        
        return $suggestions;
    }
    
    /**
     * Paginate inventory movements with filters
     */
    public function paginateWithFilters(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $perPage = $this->validatePerPage($perPage);
        
        $query = InventoryMovement::query();
        
        // Apply filters
        if (!empty($filters['item_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where(function ($q2) use ($filters) {
                    $q2->where('inventoriable_type', Item::class)
                        ->where('inventoriable_id', $filters['item_id']);
                })->orWhere(function ($q2) use ($filters) {
                    $variantIds = ItemVariant::where('item_id', $filters['item_id'])->pluck('id');
                    $q2->where('inventoriable_type', ItemVariant::class)
                        ->whereIn('inventoriable_id', $variantIds);
                });
            });
        }
        
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        if (!empty($filters['movement_type'])) {
            $types = is_array($filters['movement_type']) ? $filters['movement_type'] : [$filters['movement_type']];
            $query->whereIn('movement_type', $types);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (!empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }
        
        if (!empty($filters['reference_id'])) {
            $query->where('reference_id', $filters['reference_id']);
        }
        
        // Sort
        $query->orderBy('created_at', 'desc');
        
        return $query->paginate($perPage, $columns, $pageName, $page);
    }
}