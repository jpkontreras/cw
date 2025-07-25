<?php

declare(strict_types=1);

namespace Colame\Item\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Data\CreateItemData;
use Colame\Item\Data\UpdateItemData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Web controller for item management
 */
class ItemController extends Controller
{
    public function __construct(
        private ItemServiceInterface $itemService,
        private FeatureFlagInterface $features
    ) {}

    /**
     * Display a listing of items
     */
    public function index(Request $request): Response
    {
        $filters = [
            'active' => $request->boolean('active', false),
            'category_id' => $request->input('category_id'),
            'location_id' => $request->input('location_id'),
        ];

        $items = $this->itemService->getItems(array_filter($filters));

        // Get feature flags for the item module
        $features = [
            'variants' => $this->features->isEnabled('item.variants'),
            'modifiers' => $this->features->isEnabled('item.modifiers'),
            'recipes' => $this->features->isEnabled('item.recipes'),
            'location_pricing' => $this->features->isEnabled('item.location_pricing'),
            'inventory_tracking' => $this->features->isEnabled('item.inventory_tracking'),
        ];

        // TODO: Get categories from category service when available
        $categories = [
            ['id' => 1, 'name' => 'Sandwiches'],
            ['id' => 2, 'name' => 'Beverages'],
            ['id' => 3, 'name' => 'Sides'],
            ['id' => 4, 'name' => 'Combos'],
        ];

        // Format items for frontend - simulate pagination structure
        $formattedItems = [
            'data' => $items->toArray(),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $items->count(),
            'total' => $items->count(),
        ];

        return Inertia::render('item/index', [
            'items' => $formattedItems,
            'filters' => $filters,
            'features' => $features,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new item
     */
    public function create(): Response
    {
        // Get feature flags for the item module
        $features = [
            'variants' => $this->features->isEnabled('item.variants'),
            'modifiers' => $this->features->isEnabled('item.modifiers'),
            'recipes' => $this->features->isEnabled('item.recipes'),
            'location_pricing' => $this->features->isEnabled('item.location_pricing'),
            'inventory_tracking' => $this->features->isEnabled('item.inventory_tracking'),
        ];

        // TODO: Get categories from category service when available
        $categories = [
            ['id' => 1, 'name' => 'Sandwiches'],
            ['id' => 2, 'name' => 'Beverages'],
            ['id' => 3, 'name' => 'Sides'],
            ['id' => 4, 'name' => 'Combos'],
        ];

        return Inertia::render('item/create', [
            'features' => $features,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created item
     */
    public function store(CreateItemData $data): \Illuminate\Http\RedirectResponse
    {
        $item = $this->itemService->createItem($data);

        return redirect()
            ->route('items.show', $item->id)
            ->with('success', 'Item created successfully');
    }

    /**
     * Display the specified item
     */
    public function show(int $id): Response
    {
        $item = $this->itemService->getItemWithRelations($id);

        // Get feature flags for the item module
        $features = [
            'variants' => $this->features->isEnabled('item.variants'),
            'modifiers' => $this->features->isEnabled('item.modifiers'),
            'recipes' => $this->features->isEnabled('item.recipes'),
            'location_pricing' => $this->features->isEnabled('item.location_pricing'),
            'inventory_tracking' => $this->features->isEnabled('item.inventory_tracking'),
        ];

        return Inertia::render('item/show', [
            'item' => $item,
            'features' => $features,
        ]);
    }

    /**
     * Show the form for editing the specified item
     */
    public function edit(int $id): Response
    {
        $item = $this->itemService->getItemWithRelations($id);

        // Get feature flags for the item module
        $features = [
            'variants' => $this->features->isEnabled('item.variants'),
            'modifiers' => $this->features->isEnabled('item.modifiers'),
            'recipes' => $this->features->isEnabled('item.recipes'),
            'location_pricing' => $this->features->isEnabled('item.location_pricing'),
            'inventory_tracking' => $this->features->isEnabled('item.inventory_tracking'),
        ];

        // TODO: Get categories from category service when available
        $categories = [
            ['id' => 1, 'name' => 'Sandwiches'],
            ['id' => 2, 'name' => 'Beverages'],
            ['id' => 3, 'name' => 'Sides'],
            ['id' => 4, 'name' => 'Combos'],
        ];

        return Inertia::render('item/edit', [
            'item' => $item,
            'features' => $features,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified item
     */
    public function update(int $id, UpdateItemData $data): \Illuminate\Http\RedirectResponse
    {
        $item = $this->itemService->updateItem($id, $data);

        return redirect()
            ->route('items.show', $item->id)
            ->with('success', 'Item updated successfully');
    }

    /**
     * Remove the specified item
     */
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $this->itemService->deleteItem($id);

        return redirect()
            ->route('items.index')
            ->with('success', 'Item deleted successfully');
    }

    /**
     * Search items
     */
    public function search(Request $request): Response
    {
        $query = $request->input('q', '');
        $items = $this->itemService->searchItems($query);

        return Inertia::render('item/search', [
            'items' => $items,
            'query' => $query,
        ]);
    }
}