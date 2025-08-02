<?php

namespace Colame\Item\Services;

use App\Core\Services\BaseService;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Contracts\ModifierRepositoryInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Data\ModifierGroupData;
use Colame\Item\Data\ItemModifierData;
use Colame\Item\Data\ModifierValidationResultData;
use Colame\Item\Data\ModifierSelectionData;
use Colame\Item\Exceptions\InvalidModifierSelectionException;
use Colame\Item\Exceptions\ModifierGroupNotFoundException;
use Colame\Item\Exceptions\ModifierNotFoundException;
use Colame\Item\Exceptions\ItemNotFoundException;
use Colame\Item\Events\ModifierGroupCreated;
use Colame\Item\Events\ModifierGroupUpdated;
use Colame\Item\Events\ModifierGroupDeleted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModifierService extends BaseService
{
    public function __construct(
        private readonly ModifierRepositoryInterface $modifierRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        FeatureFlagInterface $features,
    ) {
        parent::__construct($features);
    }
    
    /**
     * Create a new modifier group
     */
    public function createModifierGroup(array $data): ModifierGroupData
    {
        $this->authorize('item.modifier_groups.create');
        
        DB::beginTransaction();
        try {
            // Validate parent group if specified
            if (!empty($data['parent_group_id'])) {
                $parent = $this->modifierRepository->findGroup($data['parent_group_id']);
                if (!$parent) {
                    throw new ModifierGroupNotFoundException($data['parent_group_id']);
                }
            }
            
            $group = $this->modifierRepository->createGroup($data);
            
            // Attach to items if specified
            if (!empty($data['item_ids'])) {
                foreach ($data['item_ids'] as $itemId) {
                    $this->attachGroupToItem($group->id, $itemId);
                }
            }
            
            DB::commit();
            
            event(new ModifierGroupCreated($group));
            
            return $group;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create modifier group', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update a modifier group
     */
    public function updateModifierGroup(int $groupId, array $data): ModifierGroupData
    {
        $this->authorize('item.modifier_groups.update');
        
        $group = $this->modifierRepository->findGroup($groupId);
        if (!$group) {
            throw new ModifierGroupNotFoundException($groupId);
        }
        
        DB::beginTransaction();
        try {
            $updatedGroup = $this->modifierRepository->updateGroup($groupId, $data);
            
            // Update item associations if specified
            if (isset($data['item_ids'])) {
                $this->syncGroupItems($groupId, $data['item_ids']);
            }
            
            DB::commit();
            
            event(new ModifierGroupUpdated($updatedGroup));
            
            return $updatedGroup;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update modifier group', [
                'group_id' => $groupId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Delete a modifier group
     */
    public function deleteModifierGroup(int $groupId): bool
    {
        $this->authorize('item.modifier_groups.delete');
        
        $group = $this->modifierRepository->findGroup($groupId);
        if (!$group) {
            throw new ModifierGroupNotFoundException($groupId);
        }
        
        DB::beginTransaction();
        try {
            // Check for child groups
            $childGroups = $this->modifierRepository->getChildGroups($groupId);
            if ($childGroups->isNotEmpty()) {
                throw new InvalidModifierSelectionException('Cannot delete group with child groups');
            }
            
            $deleted = $this->modifierRepository->deleteGroup($groupId);
            
            DB::commit();
            
            event(new ModifierGroupDeleted($groupId));
            
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete modifier group', [
                'group_id' => $groupId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create a new modifier
     */
    public function createModifier(array $data): ItemModifierData
    {
        $this->authorize('item.modifiers.create');
        
        // Validate group exists
        $group = $this->modifierRepository->findGroup($data['modifier_group_id']);
        if (!$group) {
            throw new ModifierGroupNotFoundException($data['modifier_group_id']);
        }
        
        // Validate location ID if dynamic pricing is enabled
        if ($this->features->isEnabled('item.dynamic_pricing') && !empty($data['location_id'])) {
            // Let location module handle validation
        }
        
        return $this->modifierRepository->createModifier($data);
    }
    
    /**
     * Update a modifier
     */
    public function updateModifier(int $modifierId, array $data): ItemModifierData
    {
        $this->authorize('item.modifiers.update');
        
        $modifier = $this->modifierRepository->findModifier($modifierId);
        if (!$modifier) {
            throw new ModifierNotFoundException($modifierId);
        }
        
        return $this->modifierRepository->updateModifier($modifierId, $data);
    }
    
    /**
     * Delete a modifier
     */
    public function deleteModifier(int $modifierId): bool
    {
        $this->authorize('item.modifiers.delete');
        
        $modifier = $this->modifierRepository->findModifier($modifierId);
        if (!$modifier) {
            throw new ModifierNotFoundException($modifierId);
        }
        
        return $this->modifierRepository->deleteModifier($modifierId);
    }
    
    /**
     * Attach a modifier group to an item
     */
    public function attachGroupToItem(int $groupId, int $itemId): void
    {
        $group = $this->modifierRepository->findGroup($groupId);
        if (!$group) {
            throw new ModifierGroupNotFoundException($groupId);
        }
        
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        $this->modifierRepository->attachGroupToItem($groupId, $itemId);
    }
    
    /**
     * Detach a modifier group from an item
     */
    public function detachGroupFromItem(int $groupId, int $itemId): void
    {
        $this->modifierRepository->detachGroupFromItem($groupId, $itemId);
    }
    
    /**
     * Get modifier groups for an item with hierarchy
     */
    public function getItemModifierGroups(int $itemId): Collection
    {
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        $groups = $this->modifierRepository->getGroupsForItem($itemId);
        
        // Build hierarchy if nested groups feature is enabled
        if ($this->features->isEnabled('item.nested_modifier_groups')) {
            return $this->buildGroupHierarchy($groups);
        }
        
        return $groups;
    }
    
    /**
     * Validate modifier selections for an item
     */
    public function validateModifierSelections(
        int $itemId,
        ?int $variantId,
        array $selections
    ): ModifierValidationResultData {
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        $groups = $this->modifierRepository->getGroupsForItem($itemId);
        $errors = [];
        $warnings = [];
        $validSelections = [];
        $totalPrice = 0;
        
        foreach ($groups as $group) {
            $groupSelections = array_filter($selections, function ($selection) use ($group) {
                return isset($selection['group_id']) && $selection['group_id'] == $group->id;
            });
            
            $selectedCount = count($groupSelections);
            
            // Validate min/max selections
            if ($group->isRequired && $selectedCount < $group->minSelections) {
                $errors[] = sprintf(
                    'Group "%s" requires at least %d selection(s), %d provided',
                    $group->name,
                    $group->minSelections,
                    $selectedCount
                );
            }
            
            if ($group->maxSelections > 0 && $selectedCount > $group->maxSelections) {
                $errors[] = sprintf(
                    'Group "%s" allows maximum %d selection(s), %d provided',
                    $group->name,
                    $group->maxSelections,
                    $selectedCount
                );
            }
            
            // Validate each selection
            foreach ($groupSelections as $selection) {
                $modifier = $this->modifierRepository->findModifier($selection['modifier_id']);
                if (!$modifier) {
                    $errors[] = sprintf('Invalid modifier ID: %d', $selection['modifier_id']);
                    continue;
                }
                
                if ($modifier->modifierGroupId != $group->id) {
                    $errors[] = sprintf(
                        'Modifier "%s" does not belong to group "%s"',
                        $modifier->name,
                        $group->name
                    );
                    continue;
                }
                
                if (!$modifier->isAvailable) {
                    $warnings[] = sprintf('Modifier "%s" is currently unavailable', $modifier->name);
                }
                
                $quantity = $selection['quantity'] ?? 1;
                $totalPrice += $modifier->priceAdjustment * $quantity;
                
                $validSelections[] = new ModifierSelectionData(
                    groupId: $group->id,
                    modifierId: $modifier->id,
                    quantity: $quantity,
                    priceAdjustment: $modifier->priceAdjustment
                );
            }
        }
        
        // Check for required groups without selections
        foreach ($groups as $group) {
            if ($group->isRequired && $group->minSelections > 0) {
                $hasSelection = collect($validSelections)->contains('groupId', $group->id);
                if (!$hasSelection) {
                    $errors[] = sprintf('Group "%s" is required', $group->name);
                }
            }
        }
        
        return new ModifierValidationResultData(
            isValid: empty($errors),
            errors: $errors,
            warnings: $warnings,
            validSelections: $validSelections,
            totalPriceAdjustment: $totalPrice
        );
    }
    
    /**
     * Sync modifier groups for an item
     */
    private function syncGroupItems(int $groupId, array $itemIds): void
    {
        $this->modifierRepository->syncGroupItems($groupId, $itemIds);
    }
    
    /**
     * Get all modifier groups with optional filters
     */
    public function getModifierGroups(array $filters = []): Collection
    {
        return $this->modifierRepository->getActiveGroups();
    }
    
    /**
     * Get a modifier group with its modifiers
     */
    public function getModifierGroupWithModifiers(int $groupId): ?ModifierGroupData
    {
        $group = $this->modifierRepository->findGroup($groupId);
        if (!$group) {
            return null;
        }
        
        // Load modifiers for the group
        $modifiers = $this->modifierRepository->getGroupModifiers($groupId);
        $group->modifiers = $modifiers;
        
        return $group;
    }
    
    /**
     * Build hierarchical structure for modifier groups
     */
    private function buildGroupHierarchy(Collection $groups): Collection
    {
        $grouped = $groups->groupBy('parentGroupId');
        
        $buildTree = function ($parentId = null) use (&$buildTree, $grouped) {
            $result = collect();
            
            if (!isset($grouped[$parentId])) {
                return $result;
            }
            
            foreach ($grouped[$parentId] as $group) {
                $children = $buildTree($group->id);
                if ($children->isNotEmpty()) {
                    $group->children = $children;
                }
                $result->push($group);
            }
            
            return $result;
        };
        
        return $buildTree();
    }
}