<?php

namespace Colame\Item\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Services\PricingService;
use Colame\Item\Services\InventoryService;
use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ItemController extends Controller
{
    public function __construct(
        private readonly ItemServiceInterface $itemService,
        private readonly PricingService $pricingService,
        private readonly InventoryService $inventoryService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Display a listing of items
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'category_id', 'location_id', 'search', 'sort', 'page']);
        $perPage = (int) $request->input('per_page', 20);
        
        $paginatedData = $this->itemService->getPaginatedItems($filters, $perPage);
        $responseData = $paginatedData->toArray();
        
        // Handle out-of-bounds pagination
        if ($request->input('page') && $responseData['pagination']['current_page'] > $responseData['pagination']['last_page']) {
            return response()->json([
                'error' => 'Page not found',
                'message' => 'The requested page does not exist',
                'last_page' => $responseData['pagination']['last_page'],
            ], 404);
        }
        
        return response()->json([
            'data' => $responseData['data'],
            'meta' => array_merge(
                $responseData['pagination'],
                ['resource' => $responseData['metadata']]
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
     * Store a newly created item
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:product,service,combo',
            'category_id' => 'nullable|integer',
            'base_price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:items,sku',
            'barcode' => 'nullable|string|unique:items,barcode',
            'track_stock' => 'boolean',
            'is_available' => 'boolean',
            'allow_modifiers' => 'boolean',
            'preparation_time' => 'nullable|integer|min:0',
            'variants' => 'nullable|array',
            'modifiers' => 'nullable|array',
        ]);
        
        $item = $this->itemService->createItem($validated);
        
        return response()->json([
            'data' => $item,
            'message' => 'Item created successfully',
        ], 201);
    }
    
    /**
     * Display the specified item
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->itemService->getItemWithRelations($id);
        
        if (!$item) {
            return response()->json([
                'error' => 'Item not found',
                'message' => 'The requested item does not exist',
            ], 404);
        }
        
        $locationId = request()->input('location_id');
        $includePrice = request()->boolean('include_price', false);
        $includeInventory = request()->boolean('include_inventory', false);
        
        $data = [
            'data' => $item,
        ];
        
        if ($includePrice && $this->features->isEnabled('item.dynamic_pricing')) {
            $data['meta']['price'] = $this->pricingService->calculatePrice($id, null, $locationId);
        }
        
        if ($includeInventory && $this->features->isEnabled('item.inventory_tracking')) {
            $data['meta']['inventory'] = $this->inventoryService->getInventoryLevel($id, null, $locationId);
        }
        
        return response()->json($data);
    }
    
    /**
     * Update the specified item
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->itemService->find($id);
        
        if (!$item) {
            return response()->json([
                'error' => 'Item not found',
                'message' => 'The requested item does not exist',
            ], 404);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|in:product,service,combo',
            'category_id' => 'nullable|integer',
            'base_price' => 'sometimes|required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:items,sku,' . $id,
            'barcode' => 'nullable|string|unique:items,barcode,' . $id,
            'track_stock' => 'boolean',
            'is_available' => 'boolean',
            'allow_modifiers' => 'boolean',
            'preparation_time' => 'nullable|integer|min:0',
            'variants' => 'nullable|array',
            'modifiers' => 'nullable|array',
        ]);
        
        $item = $this->itemService->updateItem($id, $validated);
        
        return response()->json([
            'data' => $item,
            'message' => 'Item updated successfully',
        ]);
    }
    
    /**
     * Remove the specified item
     */
    public function destroy(int $id): JsonResponse
    {
        $item = $this->itemService->find($id);
        
        if (!$item) {
            return response()->json([
                'error' => 'Item not found',
                'message' => 'The requested item does not exist',
            ], 404);
        }
        
        $this->itemService->deleteItem($id);
        
        return response()->json([
            'message' => 'Item deleted successfully',
        ], 204);
    }
    
    /**
     * Search items
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1',
            'location_id' => 'nullable|integer',
            'with_availability' => 'boolean',
            'with_price' => 'boolean',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);
        
        $items = $this->itemService->searchItems($validated['q'], [
            'location_id' => $validated['location_id'] ?? null,
            'with_availability' => $validated['with_availability'] ?? false,
            'with_price' => $validated['with_price'] ?? false,
            'limit' => $validated['limit'] ?? 20,
        ]);
        
        return response()->json([
            'data' => $items,
            'meta' => [
                'query' => $validated['q'],
                'count' => $items->count(),
            ],
        ]);
    }
    
    /**
     * Check item availability
     */
    public function checkAvailability(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
        ]);
        
        $item = $this->itemService->find($id);
        
        if (!$item) {
            return response()->json([
                'error' => 'Item not found',
                'message' => 'The requested item does not exist',
            ], 404);
        }
        
        $available = $this->itemService->checkAvailability(
            $id,
            $validated['quantity'],
            $validated['variant_id'] ?? null,
            $validated['location_id'] ?? null
        );
        
        return response()->json([
            'data' => [
                'available' => $available,
                'item_id' => $id,
                'quantity_requested' => $validated['quantity'],
            ],
        ]);
    }
    
    /**
     * Get item price calculation
     */
    public function calculatePrice(Request $request, int $id): JsonResponse
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Dynamic pricing is not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'modifier_ids' => 'nullable|array',
            'modifier_ids.*' => 'integer|exists:item_modifiers,id',
            'datetime' => 'nullable|date',
        ]);
        
        $item = $this->itemService->find($id);
        
        if (!$item) {
            return response()->json([
                'error' => 'Item not found',
                'message' => 'The requested item does not exist',
            ], 404);
        }
        
        $calculation = $this->pricingService->calculatePrice(
            $id,
            $validated['variant_id'] ?? null,
            $validated['location_id'] ?? null,
            $validated['modifier_ids'] ?? null,
            $validated['datetime'] ? Carbon::parse($validated['datetime']) : null
        );
        
        return response()->json([
            'data' => $calculation,
        ]);
    }
    
    /**
     * Bulk operations
     */
    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operation' => 'required|string|in:update,delete,check_availability',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|exists:items,id',
            'data' => 'required_if:operation,update|array',
        ]);
        
        $results = match($validated['operation']) {
            'update' => $this->itemService->bulkUpdate(
                $validated['item_ids'],
                'update',
                $validated['data']
            ),
            'delete' => $this->itemService->bulkDelete($validated['item_ids']),
            'check_availability' => $this->itemService->bulkCheckAvailability(
                $validated['item_ids'],
                $validated['data']['location_id'] ?? null
            ),
        };
        
        return response()->json([
            'data' => $results,
            'message' => "Bulk {$validated['operation']} completed successfully",
        ]);
    }
}