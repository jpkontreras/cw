<?php

namespace Colame\Item\Repositories;

use App\Core\Traits\ValidatesPagination;
use Colame\Item\Contracts\ModifierRepositoryInterface;
use Colame\Item\Data\ModifierGroupData;
use Colame\Item\Data\ItemModifierData;
use Colame\Item\Models\ModifierGroup;
use Colame\Item\Models\ItemModifier;
use Colame\Item\Models\ItemModifierGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ModifierRepository implements ModifierRepositoryInterface
{
    use ValidatesPagination;
    
    /**
     * Find a modifier group by ID
     */
    public function findGroup(int $id): ?ModifierGroupData
    {
        $group = ModifierGroup::find($id);
        
        return $group ? ModifierGroupData::from($group) : null;
    }
    
    /**
     * Find a modifier by ID
     */
    public function findModifier(int $id): ?ItemModifierData
    {
        $modifier = ItemModifier::find($id);
        
        return $modifier ? ItemModifierData::from($modifier) : null;
    }
    
    /**
     * Get all modifier groups for an item
     */
    public function getGroupsForItem(int $itemId): Collection
    {
        return DB::table('item_modifier_groups')
            ->join('modifier_groups', 'modifier_groups.id', '=', 'item_modifier_groups.modifier_group_id')
            ->where('item_modifier_groups.item_id', $itemId)
            ->where('modifier_groups.is_active', true)
            ->whereNull('modifier_groups.deleted_at')
            ->orderBy('item_modifier_groups.sort_order')
            ->select('modifier_groups.*')
            ->get()
            ->map(fn($group) => ModifierGroupData::from($group));
    }
    
    /**
     * Get all modifiers in a group
     */
    public function getModifiersInGroup(int $groupId): Collection
    {
        return ItemModifier::where('modifier_group_id', $groupId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($modifier) => ItemModifierData::from($modifier));
    }
    
    /**
     * Get active modifier groups
     */
    public function getActiveGroups(): Collection
    {
        return ModifierGroup::active()
            ->orderBy('name')
            ->get()
            ->map(fn($group) => ModifierGroupData::from($group));
    }
    
    /**
     * Create a new modifier group
     */
    public function createGroup(array $data): ModifierGroupData
    {
        $group = ModifierGroup::create($data);
        
        return ModifierGroupData::from($group);
    }
    
    /**
     * Update a modifier group
     */
    public function updateGroup(int $id, array $data): ModifierGroupData
    {
        $group = ModifierGroup::findOrFail($id);
        $group->update($data);
        
        return ModifierGroupData::from($group->fresh());
    }
    
    /**
     * Create a new modifier
     */
    public function createModifier(array $data): ItemModifierData
    {
        $modifier = ItemModifier::create($data);
        
        return ItemModifierData::from($modifier);
    }
    
    /**
     * Update a modifier
     */
    public function updateModifier(int $id, array $data): ItemModifierData
    {
        $modifier = ItemModifier::findOrFail($id);
        $modifier->update($data);
        
        return ItemModifierData::from($modifier->fresh());
    }
    
    /**
     * Attach modifier groups to an item
     */
    public function attachGroupsToItem(int $itemId, array $groupIds, array $sortOrders = []): void
    {
        foreach ($groupIds as $index => $groupId) {
            ItemModifierGroup::create([
                'item_id' => $itemId,
                'modifier_group_id' => $groupId,
                'sort_order' => $sortOrders[$index] ?? $index,
            ]);
        }
    }
    
    /**
     * Detach modifier groups from an item
     */
    public function detachGroupsFromItem(int $itemId, array $groupIds): void
    {
        ItemModifierGroup::where('item_id', $itemId)
            ->whereIn('modifier_group_id', $groupIds)
            ->delete();
    }
    
    /**
     * Sync modifier groups for an item
     */
    public function syncGroupsForItem(int $itemId, array $groupData): void
    {
        // Delete existing associations
        ItemModifierGroup::where('item_id', $itemId)->delete();
        
        // Create new associations
        foreach ($groupData as $index => $data) {
            if (is_array($data)) {
                ItemModifierGroup::create([
                    'item_id' => $itemId,
                    'modifier_group_id' => $data['id'] ?? $data['modifier_group_id'],
                    'sort_order' => $data['sort_order'] ?? $index,
                ]);
            } else {
                // If just an ID is provided
                ItemModifierGroup::create([
                    'item_id' => $itemId,
                    'modifier_group_id' => $data,
                    'sort_order' => $index,
                ]);
            }
        }
    }
    
    /**
     * Validate modifier selections for an item
     */
    public function validateSelections(int $itemId, array $selections): bool
    {
        // Get all modifier groups for the item
        $itemGroups = DB::table('item_modifier_groups')
            ->join('modifier_groups', 'modifier_groups.id', '=', 'item_modifier_groups.modifier_group_id')
            ->where('item_modifier_groups.item_id', $itemId)
            ->where('modifier_groups.is_active', true)
            ->whereNull('modifier_groups.deleted_at')
            ->select('modifier_groups.*')
            ->get();
        
        // Group selections by modifier group
        $selectionsByGroup = [];
        foreach ($selections as $selection) {
            $modifierId = $selection['modifier_id'] ?? $selection;
            $quantity = $selection['quantity'] ?? 1;
            
            $modifier = ItemModifier::find($modifierId);
            if (!$modifier || !$modifier->is_active) {
                return false;
            }
            
            $groupId = $modifier->modifier_group_id;
            if (!isset($selectionsByGroup[$groupId])) {
                $selectionsByGroup[$groupId] = [];
            }
            
            $selectionsByGroup[$groupId][] = [
                'modifier_id' => $modifierId,
                'quantity' => $quantity,
            ];
        }
        
        // Validate each group
        foreach ($itemGroups as $group) {
            $groupSelections = $selectionsByGroup[$group->id] ?? [];
            $selectionCount = count($groupSelections);
            
            // Check required groups
            if ($group->is_required && $selectionCount === 0) {
                return false;
            }
            
            // Check minimum selections
            if ($selectionCount < $group->min_selections) {
                return false;
            }
            
            // Check maximum selections
            if ($group->max_selections !== null && $selectionCount > $group->max_selections) {
                return false;
            }
            
            // Check single selection type
            if ($group->selection_type === 'single' && $selectionCount > 1) {
                return false;
            }
            
            // Validate quantities
            foreach ($groupSelections as $selection) {
                $modifier = ItemModifier::find($selection['modifier_id']);
                if ($selection['quantity'] > $modifier->max_quantity) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Calculate total price impact of modifiers
     */
    public function calculatePriceImpact(array $modifierIds, array $quantities = []): float
    {
        $total = 0;
        
        foreach ($modifierIds as $index => $modifierId) {
            $modifier = ItemModifier::find($modifierId);
            if ($modifier && $modifier->is_active) {
                $quantity = $quantities[$index] ?? 1;
                $quantity = min($quantity, $modifier->max_quantity);
                $total += $modifier->price_adjustment * $quantity;
            }
        }
        
        return $total;
    }
    
    /**
     * Delete a modifier group
     */
    public function deleteGroup(int $id): bool
    {
        $group = ModifierGroup::find($id);
        
        return $group ? $group->delete() : false;
    }
    
    /**
     * Delete a modifier
     */
    public function deleteModifier(int $id): bool
    {
        $modifier = ItemModifier::find($id);
        
        return $modifier ? $modifier->delete() : false;
    }
    
    /**
     * Restore a soft deleted modifier group
     */
    public function restoreGroup(int $id): bool
    {
        $group = ModifierGroup::withTrashed()->find($id);
        
        return $group ? $group->restore() : false;
    }
    
    /**
     * Get default modifiers for a group
     */
    public function getDefaultModifiers(int $groupId): Collection
    {
        return ItemModifier::where('modifier_group_id', $groupId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($modifier) => ItemModifierData::from($modifier));
    }
    
    /**
     * Paginate modifier groups with filters
     */
    public function paginateWithFilters(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $perPage = $this->validatePerPage($perPage);
        
        $query = ModifierGroup::query();
        
        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['is_required'])) {
            $query->where('is_required', $filters['is_required']);
        }
        
        if (!empty($filters['selection_type'])) {
            $query->where('selection_type', $filters['selection_type']);
        }
        
        // Sort
        if (!empty($filters['sort'])) {
            $sortField = ltrim($filters['sort'], '-');
            $sortDirection = str_starts_with($filters['sort'], '-') ? 'desc' : 'asc';
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('name');
        }
        
        return $query->paginate($perPage, $columns, $pageName, $page);
    }
    
    /**
     * Find entity by ID
     */
    public function find(int $id): ?object
    {
        return ModifierGroup::find($id);
    }
    
    /**
     * Find entity by ID or throw exception
     */
    public function findOrFail(int $id): object
    {
        return ModifierGroup::findOrFail($id);
    }
    
    /**
     * Get all entities
     */
    public function all(): array
    {
        return ModifierGroup::all()->map(fn($group) => ModifierGroupData::from($group))->toArray();
    }
    
    /**
     * Get paginated entities
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $perPage = $this->validatePerPage($perPage);
        return ModifierGroup::paginate($perPage, $columns, $pageName, $page);
    }
    
    /**
     * Create new entity
     */
    public function create(array $data): object
    {
        return ModifierGroup::create($data);
    }
    
    /**
     * Update existing entity
     */
    public function update(int $id, array $data): bool
    {
        $group = ModifierGroup::findOrFail($id);
        return $group->update($data);
    }
    
    /**
     * Delete entity
     */
    public function delete(int $id): bool
    {
        $group = ModifierGroup::findOrFail($id);
        return $group->delete();
    }
    
    /**
     * Check if entity exists
     */
    public function exists(int $id): bool
    {
        return ModifierGroup::where('id', $id)->exists();
    }
}