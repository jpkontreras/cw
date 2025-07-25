<?php

declare(strict_types=1);

namespace Colame\Item\Repositories;

use Colame\Item\Contracts\ItemModifierRepositoryInterface;
use Colame\Item\Data\ItemModifierData;
use Colame\Item\Data\ModifierGroupData;
use Colame\Item\Models\ItemModifier;
use Colame\Item\Models\ItemModifierGroup;
use Illuminate\Support\Collection;

/**
 * Item modifier repository implementation
 */
class ItemModifierRepository implements ItemModifierRepositoryInterface
{
    /**
     * Find a modifier by ID
     */
    public function find(int $id): ?ItemModifierData
    {
        $modifier = ItemModifier::find($id);
        
        return $modifier ? ItemModifierData::from($modifier) : null;
    }

    /**
     * Get all modifiers for an item
     */
    public function getByItem(int $itemId): Collection
    {
        $modifierIds = \DB::table('item_modifier_group_items')
            ->join('item_modifiers', 'item_modifier_group_items.modifier_group_id', '=', 'item_modifiers.group_id')
            ->where('item_modifier_group_items.item_id', $itemId)
            ->pluck('item_modifiers.id');
        
        return ItemModifier::whereIn('id', $modifierIds)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($modifier) => ItemModifierData::from($modifier));
    }

    /**
     * Get all modifiers in a group
     */
    public function getByGroup(int $groupId): Collection
    {
        return ItemModifier::where('group_id', $groupId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($modifier) => ItemModifierData::from($modifier));
    }

    /**
     * Find a modifier group
     */
    public function findGroup(int $id): ?ModifierGroupData
    {
        $group = ItemModifierGroup::with('modifiers')->find($id);
        
        if (!$group) {
            return null;
        }
        
        $modifiers = $group->modifiers->map(fn($modifier) => ItemModifierData::from($modifier));
        
        return new ModifierGroupData(
            id: $group->id,
            name: $group->name,
            description: $group->description,
            type: $group->type,
            isRequired: $group->is_required,
            minSelections: $group->min_selections,
            maxSelections: $group->max_selections,
            sortOrder: $group->sort_order,
            modifiers: $modifiers,
        );
    }

    /**
     * Get all modifier groups for an item
     */
    public function getGroupsByItem(int $itemId): Collection
    {
        $groups = ItemModifierGroup::whereHas('items', function ($query) use ($itemId) {
            $query->where('items.id', $itemId);
        })
        ->with('modifiers')
        ->orderBy('sort_order')
        ->get();
        
        return $groups->map(function ($group) {
            $modifiers = $group->modifiers->map(fn($modifier) => ItemModifierData::from($modifier));
            
            return new ModifierGroupData(
                id: $group->id,
                name: $group->name,
                description: $group->description,
                type: $group->type,
                isRequired: $group->is_required,
                minSelections: $group->min_selections,
                maxSelections: $group->max_selections,
                sortOrder: $group->sort_order,
                modifiers: $modifiers,
            );
        });
    }

    /**
     * Create a new modifier
     */
    public function create(array $data): ItemModifierData
    {
        $modifier = ItemModifier::create($data);
        
        return ItemModifierData::from($modifier);
    }

    /**
     * Create a new modifier group
     */
    public function createGroup(array $data): ModifierGroupData
    {
        $group = ItemModifierGroup::create($data);
        
        return new ModifierGroupData(
            id: $group->id,
            name: $group->name,
            description: $group->description,
            type: $group->type,
            isRequired: $group->is_required,
            minSelections: $group->min_selections,
            maxSelections: $group->max_selections,
            sortOrder: $group->sort_order,
            modifiers: collect(),
        );
    }

    /**
     * Update a modifier
     */
    public function update(int $id, array $data): ?ItemModifierData
    {
        $modifier = ItemModifier::find($id);
        
        if (!$modifier) {
            return null;
        }
        
        $modifier->update($data);
        
        return ItemModifierData::from($modifier);
    }

    /**
     * Delete a modifier
     */
    public function delete(int $id): bool
    {
        $modifier = ItemModifier::find($id);
        
        if (!$modifier) {
            return false;
        }
        
        return $modifier->delete();
    }
}