<?php

namespace Colame\Item\Contracts;

use App\Core\Contracts\BaseRepositoryInterface;
use Colame\Item\Data\ModifierGroupData;
use Colame\Item\Data\ItemModifierData;
use Illuminate\Support\Collection;

interface ModifierRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a modifier group by ID
     */
    public function findGroup(int $id): ?ModifierGroupData;
    
    /**
     * Find a modifier by ID
     */
    public function findModifier(int $id): ?ItemModifierData;
    
    /**
     * Get all modifier groups for an item
     */
    public function getGroupsForItem(int $itemId): Collection;
    
    /**
     * Get all modifiers in a group
     */
    public function getModifiersInGroup(int $groupId): Collection;
    
    /**
     * Get active modifier groups
     */
    public function getActiveGroups(): Collection;
    
    /**
     * Create a new modifier group
     */
    public function createGroup(array $data): ModifierGroupData;
    
    /**
     * Update a modifier group
     */
    public function updateGroup(int $id, array $data): ModifierGroupData;
    
    /**
     * Create a new modifier
     */
    public function createModifier(array $data): ItemModifierData;
    
    /**
     * Update a modifier
     */
    public function updateModifier(int $id, array $data): ItemModifierData;
    
    /**
     * Attach modifier groups to an item
     */
    public function attachGroupsToItem(int $itemId, array $groupIds, array $sortOrders = []): void;
    
    /**
     * Detach modifier groups from an item
     */
    public function detachGroupsFromItem(int $itemId, array $groupIds): void;
    
    /**
     * Sync modifier groups for an item
     */
    public function syncGroupsForItem(int $itemId, array $groupData): void;
    
    /**
     * Validate modifier selections for an item
     */
    public function validateSelections(int $itemId, array $selections): bool;
    
    /**
     * Calculate total price impact of modifiers
     */
    public function calculatePriceImpact(array $modifierIds, array $quantities = []): float;
    
    /**
     * Delete a modifier group
     */
    public function deleteGroup(int $id): bool;
    
    /**
     * Delete a modifier
     */
    public function deleteModifier(int $id): bool;
    
    /**
     * Restore a soft deleted modifier group
     */
    public function restoreGroup(int $id): bool;
    
    /**
     * Get default modifiers for a group
     */
    public function getDefaultModifiers(int $groupId): Collection;
}