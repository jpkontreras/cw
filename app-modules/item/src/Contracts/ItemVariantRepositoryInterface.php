<?php

declare(strict_types=1);

namespace Colame\Item\Contracts;

use Colame\Item\Data\ItemVariantData;
use Illuminate\Support\Collection;

/**
 * Item variant repository interface
 * 
 * Manages product variants (size, color, etc.)
 */
interface ItemVariantRepositoryInterface
{
    /**
     * Find a variant by ID
     * 
     * @param int $id
     * @return ItemVariantData|null
     */
    public function find(int $id): ?ItemVariantData;

    /**
     * Get all variants for an item
     * 
     * @param int $itemId
     * @return Collection<ItemVariantData>
     */
    public function getByItem(int $itemId): Collection;

    /**
     * Create a new variant
     * 
     * @param array $data
     * @return ItemVariantData
     */
    public function create(array $data): ItemVariantData;

    /**
     * Update a variant
     * 
     * @param int $id
     * @param array $data
     * @return ItemVariantData|null
     */
    public function update(int $id, array $data): ?ItemVariantData;

    /**
     * Delete a variant
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Check variant availability
     * 
     * @param int $id
     * @param int $quantity
     * @return bool
     */
    public function checkAvailability(int $id, int $quantity = 1): bool;

    /**
     * Get variant price
     * 
     * @param int $id
     * @param int|null $locationId
     * @return float
     */
    public function getPrice(int $id, ?int $locationId = null): float;
}