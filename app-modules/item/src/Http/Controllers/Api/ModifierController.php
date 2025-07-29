<?php

namespace Colame\Item\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Item\Services\ModifierService;
use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModifierController extends Controller
{
    public function __construct(
        private readonly ModifierService $modifierService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Get modifier groups for an item
     */
    public function getItemModifierGroups(int $itemId): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
        try {
            $groups = $this->modifierService->getItemModifierGroups($itemId);
            
            return response()->json([
                'data' => $groups,
                'meta' => [
                    'item_id' => $itemId,
                    'count' => $groups->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Item not found',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
    
    /**
     * Validate modifier selections
     */
    public function validateSelections(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'selections' => 'required|array',
            'selections.*.group_id' => 'required|integer|exists:modifier_groups,id',
            'selections.*.modifier_id' => 'required|integer|exists:item_modifiers,id',
            'selections.*.quantity' => 'nullable|integer|min:1',
        ]);
        
        try {
            $result = $this->modifierService->validateModifierSelections(
                $validated['item_id'],
                $validated['variant_id'] ?? null,
                $validated['selections']
            );
            
            return response()->json([
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get all modifier groups
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
        $filters = $request->only(['search', 'parent_group_id', 'item_id']);
        $groups = $this->modifierService->getModifierGroups($filters);
        
        return response()->json([
            'data' => $groups,
            'meta' => [
                'count' => $groups->count(),
                'features' => [
                    'nested_groups' => $this->features->isEnabled('item.nested_modifier_groups'),
                ],
            ],
        ]);
    }
    
    /**
     * Get modifier group details
     */
    public function show(int $id): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
        $group = $this->modifierService->getModifierGroupWithModifiers($id);
        
        if (!$group) {
            return response()->json([
                'error' => 'Modifier group not found',
                'message' => 'The requested modifier group does not exist',
            ], 404);
        }
        
        return response()->json([
            'data' => $group,
        ]);
    }
    
    /**
     * Create modifier group
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
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
        
        try {
            $group = $this->modifierService->createModifierGroup($validated);
            
            return response()->json([
                'data' => $group,
                'message' => 'Modifier group created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Creation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Update modifier group
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'parent_group_id' => 'nullable|integer|exists:modifier_groups,id',
            'min_selections' => 'sometimes|required|integer|min:0',
            'max_selections' => 'sometimes|required|integer|min:0',
            'is_required' => 'boolean',
            'allow_multiple' => 'boolean',
            'display_order' => 'nullable|integer',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer|exists:items,id',
        ]);
        
        try {
            $group = $this->modifierService->updateModifierGroup($id, $validated);
            
            return response()->json([
                'data' => $group,
                'message' => 'Modifier group updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Delete modifier group
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
        try {
            $this->modifierService->deleteModifierGroup($id);
            
            return response()->json([
                'message' => 'Modifier group deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Deletion failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Create modifier within a group
     */
    public function storeModifier(Request $request, int $groupId): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
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
        
        try {
            $modifier = $this->modifierService->createModifier($validated);
            
            return response()->json([
                'data' => $modifier,
                'message' => 'Modifier created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Creation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Update modifier
     */
    public function updateModifier(Request $request, int $groupId, int $modifierId): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:item_modifiers,sku,' . $modifierId,
            'price_adjustment' => 'sometimes|required|numeric',
            'is_available' => 'boolean',
            'display_order' => 'nullable|integer',
        ]);
        
        try {
            $modifier = $this->modifierService->updateModifier($modifierId, $validated);
            
            return response()->json([
                'data' => $modifier,
                'message' => 'Modifier updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Delete modifier
     */
    public function destroyModifier(int $groupId, int $modifierId): JsonResponse
    {
        if (!$this->features->isEnabled('item.modifiers')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Modifiers are not enabled',
            ], 404);
        }
        
        try {
            $this->modifierService->deleteModifier($modifierId);
            
            return response()->json([
                'message' => 'Modifier deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Deletion failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}