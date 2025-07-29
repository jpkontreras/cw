<?php

namespace Colame\Item\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Item\Services\InventoryService;
use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Get inventory levels
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Inventory tracking is not enabled',
            ], 404);
        }
        
        $locationId = $request->input('location_id');
        $filters = $request->only(['search', 'category_id', 'low_stock', 'out_of_stock', 'sort', 'page']);
        $perPage = (int) $request->input('per_page', 20);
        
        $paginatedData = $this->inventoryService->getPaginatedInventory($filters, $perPage, $locationId);
        $responseData = $paginatedData->toArray();
        
        return response()->json([
            'data' => $responseData['data'],
            'meta' => array_merge(
                $responseData['pagination'],
                [
                    'resource' => $responseData['metadata'],
                    'inventory_value' => $this->inventoryService->calculateInventoryValue($locationId),
                ]
            ),
            'links' => [
                'self' => request()->fullUrl(),
                'first' => $responseData['pagination']['first_page_url'],
                'last' => $responseData['pagination']['last_page_url'],
                'prev' => $responseData['pagination']['prev_page_url'],
                'next' => $responseData['pagination']['next_page_url'],
            ],
        ]);
    }
    
    /**
     * Get inventory level for a specific item
     */
    public function show(Request $request, int $itemId): JsonResponse
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Inventory tracking is not enabled',
            ], 404);
        }
        
        $variantId = $request->input('variant_id');
        $locationId = $request->input('location_id');
        
        try {
            $inventory = $this->inventoryService->getInventoryLevel($itemId, $variantId, $locationId);
            
            if (!$inventory) {
                return response()->json([
                    'error' => 'Inventory not found',
                    'message' => 'No inventory record found for the specified item',
                ], 404);
            }
            
            return response()->json([
                'data' => $inventory,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Item not found',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
    
    /**
     * Adjust inventory
     */
    public function adjust(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Inventory tracking is not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'quantity_change' => 'required|numeric|not_in:0',
            'adjustment_type' => 'required|string',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
        ]);
        
        try {
            $adjustment = $this->inventoryService->adjustInventory($validated);
            
            return response()->json([
                'data' => $adjustment,
                'message' => 'Inventory adjusted successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Adjustment failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Transfer stock between locations
     */
    public function transfer(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.stock_transfers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Stock transfers are not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'from_location_id' => 'required|integer|different:to_location_id',
            'to_location_id' => 'required|integer|different:from_location_id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $transfer = $this->inventoryService->transferStock($validated);
            
            return response()->json([
                'data' => $transfer,
                'message' => 'Stock transfer initiated successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Transfer failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Reserve stock
     */
    public function reserve(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.stock_reservation')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Stock reservation is not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
        ]);
        
        try {
            $reserved = $this->inventoryService->reserveStock(
                $validated['item_id'],
                $validated['quantity'],
                $validated['variant_id'] ?? null,
                $validated['location_id'] ?? null,
                $validated['reference_type'] ?? null,
                $validated['reference_id'] ?? null
            );
            
            return response()->json([
                'data' => ['reserved' => $reserved],
                'message' => $reserved ? 'Stock reserved successfully' : 'Failed to reserve stock',
            ], $reserved ? 201 : 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Reservation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Release reserved stock
     */
    public function release(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.stock_reservation')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Stock reservation is not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
        ]);
        
        try {
            $released = $this->inventoryService->releaseReservedStock(
                $validated['item_id'],
                $validated['quantity'],
                $validated['variant_id'] ?? null,
                $validated['location_id'] ?? null,
                $validated['reference_type'] ?? null,
                $validated['reference_id'] ?? null
            );
            
            return response()->json([
                'data' => ['released' => $released],
                'message' => $released ? 'Stock released successfully' : 'Failed to release stock',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Release failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get low stock items
     */
    public function lowStock(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Inventory tracking is not enabled',
            ], 404);
        }
        
        $locationId = $request->input('location_id');
        $lowStockItems = $this->inventoryService->getLowStockItems($locationId);
        
        return response()->json([
            'data' => $lowStockItems,
            'meta' => [
                'count' => $lowStockItems->count(),
                'location_id' => $locationId,
            ],
        ]);
    }
    
    /**
     * Get items to reorder
     */
    public function reorderList(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.auto_reorder')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Auto reorder is not enabled',
            ], 404);
        }
        
        $locationId = $request->input('location_id');
        $itemsToReorder = $this->inventoryService->getItemsToReorder($locationId);
        
        return response()->json([
            'data' => $itemsToReorder,
            'meta' => [
                'count' => $itemsToReorder->count(),
                'location_id' => $locationId,
            ],
        ]);
    }
    
    /**
     * Update reorder levels
     */
    public function updateReorderLevels(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.auto_reorder')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Auto reorder is not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'min_quantity' => 'required|numeric|min:0',
            'reorder_quantity' => 'required|numeric|min:0.01',
            'max_quantity' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $inventory = $this->inventoryService->updateReorderLevels(
                $validated['item_id'],
                $validated['variant_id'],
                $validated['location_id'],
                $validated['min_quantity'],
                $validated['reorder_quantity'],
                $validated['max_quantity']
            );
            
            return response()->json([
                'data' => $inventory,
                'message' => 'Reorder levels updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get movement history
     */
    public function history(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Inventory tracking is not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        try {
            $history = $this->inventoryService->getMovementHistory(
                $validated['item_id'],
                $validated['variant_id'] ?? null,
                $validated['location_id'] ?? null,
                $validated['start_date'] ? Carbon::parse($validated['start_date']) : null,
                $validated['end_date'] ? Carbon::parse($validated['end_date']) : null
            );
            
            return response()->json([
                'data' => $history,
                'meta' => [
                    'count' => $history->count(),
                    'filters' => $validated,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve history',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Perform stock take
     */
    public function stockTake(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Inventory tracking is not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'counts' => 'required|array|min:1',
            'counts.*.item_id' => 'required|integer|exists:items,id',
            'counts.*.variant_id' => 'nullable|integer|exists:item_variants,id',
            'counts.*.location_id' => 'nullable|integer',
            'counts.*.counted_quantity' => 'required|numeric|min:0',
            'counts.*.notes' => 'nullable|string',
        ]);
        
        try {
            $results = $this->inventoryService->performStockTake($validated['counts']);
            
            return response()->json([
                'data' => $results,
                'message' => "Stock take completed. {$results['adjusted']} items adjusted.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Stock take failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}