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
        
        return Inertia::render('item/modifiers/index', [
            'modifier_groups' => $this->modifierService->getModifierGroups($filters),
            'features' => [
                'nested_groups' => $this->features->isEnabled('item.nested_modifier_groups'),
                'required_modifiers' => $this->features->isEnabled('item.required_modifiers'),
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
                ? $this->modifierService->getModifierGroups(['parent_group_id' => null])
                : [],
            'items' => $this->itemService->getItemsForSelect(['allow_modifiers' => true]),
        ]);
    }
    
    /**
     * Store a newly created modifier group
     */
    public function store(Request $request)
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
        
        $group = $this->modifierService->createModifierGroup($validated);
        
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
}