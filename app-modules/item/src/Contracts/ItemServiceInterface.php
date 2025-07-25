<?php

declare(strict_types=1);

namespace Colame\Item\Contracts;

use Colame\Item\Data\CreateItemData;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use Colame\Item\Data\UpdateItemData;
use Illuminate\Support\Collection;

/**
 * Item service interface
 * 
 * Defines the business logic contract for item management
 */
interface ItemServiceInterface
{
    /**
     * Create a new item
     * 
     * @param CreateItemData $data
     * @return ItemData
     */
    public function createItem(CreateItemData $data): ItemData;

    /**
     * Update an existing item
     * 
     * @param int $id
     * @param UpdateItemData $data
     * @return ItemData
     */
    public function updateItem(int $id, UpdateItemData $data): ItemData;

    /**
     * Delete an item
     * 
     * @param int $id
     * @return bool
     */
    public function deleteItem(int $id): bool;

    /**
     * Get item by ID
     * 
     * @param int $id
     * @return ItemData
     */
    public function getItem(int $id): ItemData;

    /**
     * Get item with all relations
     * 
     * @param int $id
     * @return ItemWithRelationsData
     */
    public function getItemWithRelations(int $id): ItemWithRelationsData;

    /**
     * Get all items
     * 
     * @param array $filters
     * @return Collection<ItemData>
     */
    public function getItems(array $filters = []): Collection;

    /**
     * Search items
     * 
     * @param string $query
     * @return Collection<ItemData>
     */
    public function searchItems(string $query): Collection;

    /**
     * Check item availability
     * 
     * @param int $id
     * @param int $quantity
     * @param int|null $locationId
     * @return bool
     */
    public function checkAvailability(int $id, int $quantity, ?int $locationId = null): bool;

    /**
     * Get item price
     * 
     * @param int $id
     * @param int|null $locationId
     * @return float
     */
    public function getPrice(int $id, ?int $locationId = null): float;

    /**
     * Import items from CSV/Excel
     * 
     * @param string $filePath
     * @param array $options
     * @return array Import results
     */
    public function importItems(string $filePath, array $options = []): array;

    /**
     * Export items to CSV/Excel
     * 
     * @param array $filters
     * @param string $format
     * @return string File path
     */
    public function exportItems(array $filters = [], string $format = 'csv'): string;
}