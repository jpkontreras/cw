<?php

namespace Colame\Item\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Item\Services\ModifierService;
use Colame\Item\Contracts\ItemServiceInterface;
use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModifierController extends Controller
{
    public function __construct(
        private readonly ModifierService $modifierService,
        private readonly ItemServiceInterface $itemService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Display a listing of modifier groups
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'parent_group_id', 'item_id']);
        $modifierGroups = $this->modifierService->getModifierGroups($filters);
        
        // Calculate stats
        $stats = [
            'total_groups' => $modifierGroups->count(),
            'active_groups' => $modifierGroups->where('is_active', true)->count(),
            'total_modifiers' => $modifierGroups->sum(fn($group) => $group->modifiers_count ?? 0),
            'avg_modifiers_per_group' => $modifierGroups->count() > 0 
                ? round($modifierGroups->sum(fn($group) => $group->modifiers_count ?? 0) / $modifierGroups->count(), 1)
                : 0,
        ];
        
        return Inertia::render('item/modifiers/index', [
            'modifier_groups' => $modifierGroups,
            'stats' => $stats,
            'popular_modifiers' => [], // TODO: Implement popular modifiers tracking
            'pagination' => null, // TODO: Add pagination if needed
            'metadata' => null, // TODO: Add metadata if needed
            'items' => [], // TODO: Get items if needed
            'features' => [
                'nested_groups' => $this->features->isEnabled('item.nested_modifier_groups'),
                'required_modifiers' => $this->features->isEnabled('item.required_modifiers'),
                'modifier_inventory' => $this->features->isEnabled('item.modifier_inventory'),
                'modifier_pricing' => $this->features->isEnabled('item.modifier_pricing'),
            ],
        ]);
    }
    
    /**
     * Show the form for creating a new modifier group
     */
    public function create(): Response
    {
        return Inertia::render('item/modifiers/create', [
            'parent_groups' => $this->features->isEnabled('item.nested_modifier_groups')
                ? $this->modifierService->getModifierGroups(['parent_group_id' => null])->map(fn($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                ])
                : [],
            'items' => [], // TODO: Implement getItemsForSelect in ItemService
        ]);
    }
    
    /**
     * Store a newly created modifier group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_group_id' => 'nullable|integer|exists:modifier_groups,id',
            'min_selections' => 'required|integer|min:0',
            'max_selections' => 'nullable|integer|min:0',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer|exists:items,id',
            'modifiers' => 'nullable|array',
            'modifiers.*.name' => 'required_with:modifiers|string|max:255',
            'modifiers.*.price_adjustment' => 'required_with:modifiers|numeric',
            'modifiers.*.is_available' => 'required_with:modifiers|boolean',
            'modifiers.*.display_order' => 'required_with:modifiers|integer',
        ]);
        
        // Map to expected field names
        $groupData = [
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'parent_group_id' => $validated['parent_group_id'] ?? null,
            'min_selections' => $validated['min_selections'],
            'max_selections' => $validated['max_selections'] ?? null,
            'is_required' => $validated['is_required'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'selection_type' => ($validated['max_selections'] === 1) ? 'single' : 'multiple',
            'display_order' => $validated['display_order'] ?? 0,
        ];
        
        $group = $this->modifierService->createModifierGroup($groupData);
        
        // Create modifiers if provided
        if (!empty($validated['modifiers'])) {
            foreach ($validated['modifiers'] as $modifierData) {
                $modifierData['modifier_group_id'] = $group->id;
                $this->modifierService->createModifier($modifierData);
            }
        }
        
        return redirect()->route('modifier.show', $group->id)
            ->with('success', 'Modifier group created successfully');
    }
    
    /**
     * Display the specified modifier group
     */
    public function show(int $id): Response
    {
        $group = $this->modifierService->getModifierGroupWithModifiers($id);
        
        if (!$group) {
            abort(404);
        }
        
        return Inertia::render('item/modifiers/show', [
            'modifier_group' => $group,
            'items_using_group' => $this->modifierService->getItemsUsingGroup($id),
        ]);
    }
    
    /**
     * Show the form for editing the specified modifier group
     */
    public function edit(int $id): Response
    {
        $group = $this->modifierService->getModifierGroupWithModifiers($id);
        
        if (!$group) {
            abort(404);
        }
        
        return Inertia::render('item/modifiers/edit', [
            'modifier_group' => $group,
            'parent_groups' => $this->features->isEnabled('item.nested_modifier_groups')
                ? $this->modifierService->getModifierGroups([
                    'parent_group_id' => null,
                    'exclude_id' => $id
                ])
                : [],
            'items' => $this->itemService->getItemsForSelect(['allow_modifiers' => true]),
        ]);
    }
    
    /**
     * Update the specified modifier group
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_group_id' => 'nullable|integer|exists:modifier_groups,id',
            'min_selections' => 'required|integer|min:0',
            'max_selections' => 'required|integer|min:0',
            'is_required' => 'boolean',
            'allow_multiple' => 'boolean',
            'display_order' => 'nullable|integer',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer|exists:items,id',
        ]);
        
        $group = $this->modifierService->updateModifierGroup($id, $validated);
        
        return redirect()->route('modifier.show', $group->id)
            ->with('success', 'Modifier group updated successfully');
    }
    
    /**
     * Remove the specified modifier group
     */
    public function destroy(int $id)
    {
        $this->modifierService->deleteModifierGroup($id);
        
        return redirect()->route('modifier.index')
            ->with('success', 'Modifier group deleted successfully');
    }
    
    /**
     * Create a new modifier within a group
     */
    public function createModifier(int $groupId): Response
    {
        $group = $this->modifierService->findModifierGroup($groupId);
        
        if (!$group) {
            abort(404);
        }
        
        return Inertia::render('item/modifiers/create-modifier', [
            'modifier_group' => $group,
            'locations' => $this->features->isEnabled('item.location_pricing') ? [] : null, // From location module
        ]);
    }
    
    /**
     * Store a new modifier within a group
     */
    public function storeModifier(Request $request, int $groupId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:item_modifiers,sku',
            'price_adjustment' => 'required|numeric',
            'is_available' => 'boolean',
            'display_order' => 'nullable|integer',
            'location_id' => 'nullable|integer',
        ]);
        
        $validated['modifier_group_id'] = $groupId;
        
        $modifier = $this->modifierService->createModifier($validated);
        
        return redirect()->route('modifier.show', $groupId)
            ->with('success', 'Modifier created successfully');
    }
    
    /**
     * Update a modifier
     */
    public function updateModifier(Request $request, int $groupId, int $modifierId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:item_modifiers,sku,' . $modifierId,
            'price_adjustment' => 'required|numeric',
            'is_available' => 'boolean',
            'display_order' => 'nullable|integer',
        ]);
        
        $modifier = $this->modifierService->updateModifier($modifierId, $validated);
        
        return redirect()->route('modifier.show', $groupId)
            ->with('success', 'Modifier updated successfully');
    }
    
    /**
     * Delete a modifier
     */
    public function destroyModifier(int $groupId, int $modifierId)
    {
        $this->modifierService->deleteModifier($modifierId);
        
        return redirect()->route('modifier.show', $groupId)
            ->with('success', 'Modifier deleted successfully');
    }
    
    /**
     * Reorder modifiers within a group
     */
    public function reorderModifiers(Request $request, int $groupId)
    {
        $validated = $request->validate([
            'modifier_ids' => 'required|array',
            'modifier_ids.*' => 'integer|exists:item_modifiers,id',
        ]);
        
        $this->modifierService->reorderModifiers($groupId, $validated['modifier_ids']);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Show bulk assign page
     */
    public function bulkAssign(): Response
    {
        $modifierGroups = $this->modifierService->getModifierGroups();
        $items = $this->itemService->getAllItems()
            ->filter(fn($item) => $item->allowModifiers ?? false)
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'category' => 'Uncategorized', // TODO: Add category name
                'has_modifiers' => false, // TODO: Check if item has modifiers
            ]);
        
        return Inertia::render('item/modifiers/bulk-assign', [
            'modifier_groups' => $modifierGroups->map(fn($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'modifiers_count' => 0, // TODO: Add modifiers count
                'is_active' => $group->isActive,
            ]),
            'items' => $items,
        ]);
    }
    
    /**
     * Process bulk assign
     */
    public function processBulkAssign(Request $request)
    {
        $validated = $request->validate([
            'modifier_group_ids' => 'required|array|min:1',
            'modifier_group_ids.*' => 'integer|exists:modifier_groups,id',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|exists:items,id',
            'action' => 'required|in:add,replace',
        ]);
        
        $successCount = 0;
        $errors = [];
        
        foreach ($validated['item_ids'] as $itemId) {
            try {
                if ($validated['action'] === 'replace') {
                    // First remove all existing modifier groups
                    $this->modifierService->syncGroupsForItem($itemId, []);
                }
                
                // Then add the selected modifier groups
                foreach ($validated['modifier_group_ids'] as $groupId) {
                    $this->modifierService->attachGroupToItem($groupId, $itemId);
                }
                
                $successCount++;
            } catch (\Exception $e) {
                $item = $this->itemService->find($itemId);
                $errors[] = "Failed to update {$item->name}: {$e->getMessage()}";
            }
        }
        
        if (count($errors) > 0) {
            return redirect()->route('modifier.bulk-assign')
                ->with('warning', "Updated {$successCount} items. Some items failed to update.")
                ->withErrors($errors);
        }
        
        return redirect()->route('modifier.index')
            ->with('success', "Successfully assigned modifier groups to {$successCount} items.");
    }
}