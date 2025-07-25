<?php

declare(strict_types=1);

namespace Colame\Item\Contracts;

use Colame\Item\Data\ItemModifierData;
use Colame\Item\Data\ModifierGroupData;
use Illuminate\Support\Collection;

/**
 * Item modifier repository interface
 * 
 * Manages item modifiers and modifier groups
 */
interface ItemModifierRepositoryInterface
{
    /**
     * Find a modifier by ID
     * 
     * @param int $id
     * @return ItemModifierData|null
     */
    public function find(int $id): ?ItemModifierData;

    /**
     * Get all modifiers for an item
     * 
     * @param int $itemId
     * @return Collection<ItemModifierData>
     */
    public function getByItem(int $itemId): Collection;

    /**
     * Get all modifiers in a group
     * 
     * @param int $groupId
     * @return Collection<ItemModifierData>
     */
    public function getByGroup(int $groupId): Collection;

    /**
     * Find a modifier group
     * 
     * @param int $id
     * @return ModifierGroupData|null
     */
    public function findGroup(int $id): ?ModifierGroupData;

    /**
     * Get all modifier groups for an item
     * 
     * @param int $itemId
     * @return Collection<ModifierGroupData>
     */
    public function getGroupsByItem(int $itemId): Collection;

    /**
     * Create a new modifier
     * 
     * @param array $data
     * @return ItemModifierData
     */
    public function create(array $data): ItemModifierData;

    /**
     * Create a new modifier group
     * 
     * @param array $data
     * @return ModifierGroupData
     */
    public function createGroup(array $data): ModifierGroupData;

    /**
     * Update a modifier
     * 
     * @param int $id
     * @param array $data
     * @return ItemModifierData|null
     */
    public function update(int $id, array $data): ?ItemModifierData;

    /**
     * Delete a modifier
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}