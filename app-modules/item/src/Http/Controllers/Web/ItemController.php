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
            'base_price' => 'nullable|numeric|min:0',
            'base_cost' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:items,sku',
            'barcode' => 'nullable|string|unique:items,barcode',
            'track_inventory' => 'boolean',
            'is_available' => 'boolean',
            'allow_modifiers' => 'boolean',
            'preparation_time' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string|url',
            'variants' => 'nullable|array',
            'modifier_groups' => 'nullable|array',
        ]);
        
        // Transform the data for the service
        $data = $validated;
        
        // Handle image URL - convert to images array format expected by service
        if (!empty($validated['image_url'])) {
            // Extract path from URL if it's a full URL
            $imagePath = $validated['image_url'];
            if (str_starts_with($imagePath, url('/'))) {
                // Remove the base URL to get just the path
                $imagePath = str_replace(url('/'), '', $imagePath);
                $imagePath = ltrim($imagePath, '/');
            }
            // Remove 'storage/' prefix if present as it will be added by the model
            if (str_starts_with($imagePath, 'storage/')) {
                $imagePath = substr($imagePath, 8);
            }
            
            $data['images'] = [
                [
                    'image_path' => $imagePath,
                    'is_primary' => true,
                    'sort_order' => 0,
                    'item_id' => 0, // Will be set by the service
                ]
            ];
            unset($data['image_url']);
        }
        
        // Map track_stock to track_inventory for consistency
        if (isset($data['track_inventory'])) {
            $data['track_stock'] = $data['track_inventory'];
            unset($data['track_inventory']);
        }
        
        $item = $this->itemService->createItem($data);
        
        return redirect()->route('item.show', $item->id)
            ->with('success', 'Item created successfully');
    }
    
    /**
     * Display the specified item
     */
    public function show(int $id): Response
    {
        $itemWithRelations = $this->itemService->getItemWithRelations($id);
        
        if (!$itemWithRelations) {
            abort(404);
        }
        
        $locationId = request()->input('location_id');
        
        // Flatten the item data structure for the frontend
        $itemData = array_merge(
            $itemWithRelations->item->toArray(),
            [
                'images' => collect($itemWithRelations->images ?? [])->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->getImageUrl(),
                        'isPrimary' => $image->isPrimary,
                        'is_primary' => $image->isPrimary, // Legacy support
                    ];
                })->toArray(),
                'variants' => $itemWithRelations->variants ?? [],
                'category_name' => $itemWithRelations->categories && count($itemWithRelations->categories) > 0 
                    ? 'Category ' . $itemWithRelations->categories[0] // Placeholder until taxonomy module provides names
                    : null,
            ]
        );
        
        return Inertia::render('item/show', [
            'item' => $itemData,
            'modifier_groups' => $this->features->isEnabled('item.modifiers') 
                ? $this->modifierService->getItemModifierGroups($id) 
                : [],
            'current_price' => $this->features->isEnabled('item.dynamic_pricing')
                ? $this->pricingService->calculatePrice($id, null, $locationId)
                : null,
            'inventory' => $this->features->isEnabled('item.inventory_tracking')
                ? $this->inventoryService->getInventoryLevel($id, null, $locationId)
                : null,
            'recipe' => $itemWithRelations->recipe,
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
        $itemWithRelations = $this->itemService->getItemWithRelations($id);
        
        if (!$itemWithRelations) {
            abort(404);
        }
        
        // Flatten the item data structure for the frontend
        $itemData = array_merge(
            $itemWithRelations->item->toArray(),
            [
                'images' => collect($itemWithRelations->images ?? [])->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->getImageUrl(),
                        'is_primary' => $image->isPrimary,
                    ];
                })->toArray(),
                'variants' => $itemWithRelations->variants ?? [],
                'bundle_items' => $itemWithRelations->bundleItems ?? [],
                'modifier_groups' => $itemWithRelations->modifierGroups ?? [],
                'tags' => $itemWithRelations->tags ?? [],
                'allergens' => $itemWithRelations->allergens ?? [],
                'category_id' => $itemWithRelations->categories && count($itemWithRelations->categories) > 0 
                    ? $itemWithRelations->categories[0] 
                    : null,
            ]
        );
        
        return Inertia::render('item/edit', [
            'item' => $itemData,
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
            'base_price' => 'nullable|numeric|min:0',
            'base_cost' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:items,sku,' . $id,
            'barcode' => 'nullable|string|unique:items,barcode,' . $id,
            'track_inventory' => 'boolean',
            'is_available' => 'boolean',
            'allow_modifiers' => 'boolean',
            'preparation_time' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string|url',
            'variants' => 'nullable|array',
            'modifier_groups' => 'nullable|array',
        ]);
        
        // Transform the data for the service
        $data = $validated;
        
        // Handle image URL - convert to images array format expected by service
        if (isset($validated['image_url'])) {
            if (!empty($validated['image_url'])) {
                // Extract path from URL if it's a full URL
                $imagePath = $validated['image_url'];
                if (str_starts_with($imagePath, url('/'))) {
                    // Remove the base URL to get just the path
                    $imagePath = str_replace(url('/'), '', $imagePath);
                    $imagePath = ltrim($imagePath, '/');
                }
                // Remove 'storage/' prefix if present as it will be added by the model
                if (str_starts_with($imagePath, 'storage/')) {
                    $imagePath = substr($imagePath, 8);
                }
                
                $data['images'] = [
                    [
                        'image_path' => $imagePath,
                        'is_primary' => true,
                        'sort_order' => 0,
                        'item_id' => $id,
                    ]
                ];
            } else {
                // Clear images if image_url is empty
                $data['images'] = [];
            }
            unset($data['image_url']);
        }
        
        // Map track_stock to track_inventory for consistency
        if (isset($data['track_inventory'])) {
            $data['track_stock'] = $data['track_inventory'];
            unset($data['track_inventory']);
        }
        
        $item = $this->itemService->updateItem($id, $data);
        
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

    /**
     * Show the AI Discovery interface for item creation
     */
    public function aiDiscovery(Request $request): Response
    {
        // Get location from Inertia shared data
        $location = $request->user()?->currentLocation;
        $currency = config('money.defaults.currency', 'CLP');

        return Inertia::render('item/ai-discovery', [
            'features' => [
                'variants' => $this->features->isEnabled('item.variants'),
                'modifiers' => $this->features->isEnabled('item.modifiers'),
                'inventory' => $this->features->isEnabled('item.inventory_tracking'),
                'ai_enabled' => config('openai.api_key') !== null,
            ],
            'regional_settings' => [
                'location' => $location?->name ?? 'Chile',
                'currency' => $currency,
                'language' => app()->getLocale(),
            ],
        ]);
    }

    /**
     * Start AI Discovery session via AJAX
     */
    public function startAiSession(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'cuisine_type' => 'nullable|string|max:100',
            'price_tier' => 'nullable|in:low,medium,high',
        ]);

        // Get location from user context
        $location = $request->user()?->currentLocation;
        $currency = config('money.defaults.currency', 'CLP');

        $context = [
            'cuisine_type' => $validated['cuisine_type'] ?? 'general',
            'location' => $location?->name ?? 'Chile',
            'currency' => $currency,
            'price_tier' => $validated['price_tier'] ?? 'medium',
            'category' => $validated['category'] ?? null,
        ];

        $session = $this->itemService->startAiDiscovery(
            $validated['item_name'],
            $validated['description'] ?? null,
            $context
        );

        if (!$session) {
            return response()->json([
                'error' => 'AI Discovery is not available. Please configure your OpenAI API key.',
            ], 503);
        }

        // Extract the last assistant message from conversation history
        $lastAssistantMessage = null;
        if (!empty($session->conversationHistory)) {
            foreach (array_reverse($session->conversationHistory) as $msg) {
                if ($msg['role'] === 'assistant') {
                    $lastAssistantMessage = $msg['content'];
                    break;
                }
            }
        }

        return response()->json([
            'session_id' => $session->sessionUuid,
            'message' => $lastAssistantMessage ?? 'Hello! Let\'s explore your menu item.',
            'extracted_data' => $session->extractedData,
        ]);
    }

    /**
     * Process AI Discovery response via AJAX
     */
    public function processAiResponse(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'response' => 'required|string',
            'selections' => 'nullable|array',
        ]);

        $context = $this->itemService->processAiResponse(
            $validated['session_id'],
            $validated['response'],
            $validated['selections'] ?? null
        );

        if (!$context) {
            return response()->json([
                'error' => 'Failed to process response',
            ], 500);
        }

        // Format response for frontend
        return response()->json([
            'next_question' => $context->nextPrompt ?? null,
            'extracted_data' => $context->collectedData ?? [],
            'current_phase' => $context->currentPhase ?? 'initial',
            'ready_to_complete' => $context->currentPhase === 'confirmation',
        ]);
    }

    /**
     * Complete AI Discovery and create item
     */
    public function completeAiDiscovery(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'type' => 'required|in:product,service,combo',
            'category' => 'nullable|string',
        ]);

        $item = $this->itemService->completeAiDiscoveryAndCreateItem(
            $validated['session_id'],
            $validated
        );

        if (!$item) {
            return response()->json([
                'error' => 'Failed to create item from AI discovery',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'item' => $item,
            'redirect' => route('item.edit', $item->id),
        ]);
    }
}