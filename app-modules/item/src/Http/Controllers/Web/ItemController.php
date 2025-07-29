<?php

namespace Colame\Item\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Services\ModifierService;
use Colame\Item\Services\PricingService;
use Colame\Item\Services\InventoryService;
use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Traits\HandlesPaginationBounds;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    use HandlesPaginationBounds;
    
    public function __construct(
        private readonly ItemServiceInterface $itemService,
        private readonly ModifierService $modifierService,
        private readonly PricingService $pricingService,
        private readonly InventoryService $inventoryService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Display a listing of items
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'type', 'category_id', 'location_id', 'search', 'sort', 'page']);
        $perPage = (int) $request->input('per_page', 20);
        
        $paginatedData = $this->itemService->getPaginatedItems($filters, $perPage);
        $responseData = $paginatedData->toArray();
        
        // Handle out-of-bounds pagination
        if ($redirect = $this->handleOutOfBoundsPagination($responseData['pagination'], $request, 'item.index')) {
            return $redirect;
        }
        
        return Inertia::render('item/index', [
            'items' => $responseData['data'],
            'pagination' => $responseData['pagination'],
            'metadata' => $responseData['metadata'],
            'features' => [
                'variants' => $this->features->isEnabled('item.variants'),
                'modifiers' => $this->features->isEnabled('item.modifiers'),
                'dynamic_pricing' => $this->features->isEnabled('item.dynamic_pricing'),
                'inventory' => $this->features->isEnabled('item.inventory_tracking'),
                'recipes' => $this->features->isEnabled('item.recipes'),
            ],
        ]);
    }
    
    /**
     * Show the form for creating a new item
     */
    public function create(): Response
    {
        return Inertia::render('item/create', [
            'categories' => [], // Will be fetched from taxonomy module
            'item_types' => $this->itemService->getItemTypes(),
            'features' => [
                'variants' => $this->features->isEnabled('item.variants'),
                'modifiers' => $this->features->isEnabled('item.modifiers'),
                'inventory' => $this->features->isEnabled('item.inventory_tracking'),
                'multiple_images' => $this->features->isEnabled('item.multiple_images'),
            ],
        ]);
    }
    
    /**
     * Store a newly created item
     */
    public function store(Request $request)
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
            'image' => 'nullable|image|max:2048',
            'variants' => 'nullable|array',
            'modifiers' => 'nullable|array',
        ]);
        
        $item = $this->itemService->createItem($validated);
        
        return redirect()->route('item.show', $item->id)
            ->with('success', 'Item created successfully');
    }
    
    /**
     * Display the specified item
     */
    public function show(int $id): Response
    {
        $item = $this->itemService->getItemWithRelations($id);
        
        if (!$item) {
            abort(404);
        }
        
        $locationId = request()->input('location_id');
        
        return Inertia::render('item/show', [
            'item' => $item,
            'modifier_groups' => $this->features->isEnabled('item.modifiers') 
                ? $this->modifierService->getItemModifierGroups($id) 
                : [],
            'current_price' => $this->features->isEnabled('item.dynamic_pricing')
                ? $this->pricingService->calculatePrice($id, null, $locationId)
                : null,
            'inventory' => $this->features->isEnabled('item.inventory_tracking')
                ? $this->inventoryService->getInventoryLevel($id, null, $locationId)
                : null,
            'features' => [
                'variants' => $this->features->isEnabled('item.variants'),
                'modifiers' => $this->features->isEnabled('item.modifiers'),
                'dynamic_pricing' => $this->features->isEnabled('item.dynamic_pricing'),
                'inventory' => $this->features->isEnabled('item.inventory_tracking'),
                'recipes' => $this->features->isEnabled('item.recipes'),
            ],
        ]);
    }
    
    /**
     * Show the form for editing the specified item
     */
    public function edit(int $id): Response
    {
        $item = $this->itemService->getItemWithRelations($id);
        
        if (!$item) {
            abort(404);
        }
        
        return Inertia::render('item/edit', [
            'item' => $item,
            'categories' => [], // Will be fetched from taxonomy module
            'item_types' => $this->itemService->getItemTypes(),
            'features' => [
                'variants' => $this->features->isEnabled('item.variants'),
                'modifiers' => $this->features->isEnabled('item.modifiers'),
                'inventory' => $this->features->isEnabled('item.inventory_tracking'),
                'multiple_images' => $this->features->isEnabled('item.multiple_images'),
            ],
        ]);
    }
    
    /**
     * Update the specified item
     */
    public function update(Request $request, int $id)
    {
        $item = $this->itemService->find($id);
        
        if (!$item) {
            abort(404);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:product,service,combo',
            'category_id' => 'nullable|integer',
            'base_price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:items,sku,' . $id,
            'barcode' => 'nullable|string|unique:items,barcode,' . $id,
            'track_stock' => 'boolean',
            'is_available' => 'boolean',
            'allow_modifiers' => 'boolean',
            'preparation_time' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'variants' => 'nullable|array',
            'modifiers' => 'nullable|array',
        ]);
        
        $item = $this->itemService->updateItem($id, $validated);
        
        return redirect()->route('item.show', $item->id)
            ->with('success', 'Item updated successfully');
    }
    
    /**
     * Remove the specified item
     */
    public function destroy(int $id)
    {
        $item = $this->itemService->find($id);
        
        if (!$item) {
            abort(404);
        }
        
        $this->itemService->deleteItem($id);
        
        return redirect()->route('item.index')
            ->with('success', 'Item deleted successfully');
    }
    
    /**
     * Search items
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $locationId = $request->input('location_id');
        
        $items = $this->itemService->searchItems($query, [
            'location_id' => $locationId,
            'with_availability' => true,
            'with_price' => true,
        ]);
        
        return response()->json([
            'items' => $items,
        ]);
    }
    
    /**
     * Bulk update items
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:items,id',
            'action' => 'required|string|in:update_availability,update_category,delete',
            'data' => 'required_unless:action,delete|array',
        ]);
        
        $result = $this->itemService->bulkUpdate(
            $validated['item_ids'],
            $validated['action'],
            $validated['data'] ?? []
        );
        
        return redirect()->back()
            ->with('success', "Successfully updated {$result['updated']} items");
    }
}