<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Data\ItemDetailsData;

/**
 * Interface for cross-module item access
 * 
 * This interface provides a contract for modules that need to access
 * item information from the Item module without creating direct dependencies.
 */
interface ItemRepositoryInterface
{
    /**
     * Get item details by ID
     * 
     * @param int $itemId
     * @return ItemDetailsData|null
     */
    public function getItemDetails(int $itemId): ?ItemDetailsData;
    
    /**
     * Get multiple item details by IDs
     * 
     * @param array<int> $itemIds
     * @return array<int, ItemDetailsData> Keyed by item ID
     */
    public function getMultipleItemDetails(array $itemIds): array;
    
    /**
     * Check if an item exists
     * 
     * @param int $itemId
     * @return bool
     */
    public function itemExists(int $itemId): bool;
    
    /**
     * Get item price
     * 
     * @param int $itemId
     * @return float|null
     */
    public function getItemPrice(int $itemId): ?float;
    
    /**
     * Get item name
     * 
     * @param int $itemId
     * @return string|null
     */
    public function getItemName(int $itemId): ?string;
}