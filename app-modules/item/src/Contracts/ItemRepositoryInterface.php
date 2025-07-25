<?php

declare(strict_types=1);

namespace Colame\Item\Contracts;

use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use Illuminate\Support\Collection;

/**
 * Item repository interface
 * 
 * Defines the contract for item-related data operations.
 * Following interface-based architecture, this repository returns DTOs exclusively.
 */
interface ItemRepositoryInterface
{
    /**
     * Find an item by ID
     * 
     * @param int $id
     * @return ItemData|null
     */
    public function find(int $id): ?ItemData;

    /**
     * Find an item with all its relations
     * 
     * @param int $id
     * @return ItemWithRelationsData|null
     */
    public function findWithRelations(int $id): ?ItemWithRelationsData;

    /**
     * Get all items
     * 
     * @return Collection<ItemData>
     */
    public function all(): Collection;

    /**
     * Get active items only
     * 
     * @return Collection<ItemData>
     */
    public function getActive(): Collection;

    /**
     * Get items by category
     * 
     * @param int $categoryId
     * @return Collection<ItemData>
     */
    public function getByCategory(int $categoryId): Collection;

    /**
     * Get items by location
     * 
     * @param int $locationId
     * @return Collection<ItemData>
     */
    public function getByLocation(int $locationId): Collection;

    /**
     * Create a new item
     * 
     * @param array $data
     * @return ItemData
     */
    public function create(array $data): ItemData;

    /**
     * Update an item
     * 
     * @param int $id
     * @param array $data
     * @return ItemData|null
     */
    public function update(int $id, array $data): ?ItemData;

    /**
     * Delete an item
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Check if item is available
     * 
     * @param int $id
     * @param int $quantity
     * @return bool
     */
    public function checkAvailability(int $id, int $quantity = 1): bool;

    /**
     * Get current price for item
     * 
     * @param int $id
     * @param int|null $locationId
     * @return float
     */
    public function getCurrentPrice(int $id, ?int $locationId = null): float;

    /**
     * Search items by name or SKU
     * 
     * @param string $query
     * @return Collection<ItemData>
     */
    public function search(string $query): Collection;
}