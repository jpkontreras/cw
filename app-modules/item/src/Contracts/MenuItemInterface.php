<?php

declare(strict_types=1);

namespace Colame\Item\Contracts;

use Colame\Item\Data\ItemMenuData;
use Spatie\LaravelData\DataCollection;

/**
 * Interface for menu module to interact with items
 * This interface allows the menu module to retrieve item information
 * without directly accessing Item models
 */
interface MenuItemInterface
{
    /**
     * Get items for menu display
     * 
     * @param array $itemIds Array of item IDs to retrieve
     * @return DataCollection<ItemMenuData>
     */
    public function getItemsForMenu(array $itemIds): DataCollection;
    
    /**
     * Get a single item for menu display
     * 
     * @param int $itemId
     * @return ItemMenuData|null
     */
    public function getItemForMenu(int $itemId): ?ItemMenuData;
    
    /**
     * Check if an item exists and is active
     * 
     * @param int $itemId
     * @return bool
     */
    public function isItemAvailable(int $itemId): bool;
    
    /**
     * Get item with modifiers for menu
     * 
     * @param int $itemId
     * @return ItemMenuData|null
     */
    public function getItemWithModifiers(int $itemId): ?ItemMenuData;
    
    /**
     * Get items by category for menu
     * 
     * @param int $categoryId
     * @return DataCollection<ItemMenuData>
     */
    public function getItemsByCategory(int $categoryId): DataCollection;
    
    /**
     * Get item price for a specific location
     * 
     * @param int $itemId
     * @param int $locationId
     * @return float|null
     */
    public function getItemPriceForLocation(int $itemId, int $locationId): ?float;
    
    /**
     * Get item stock for a specific location
     * 
     * @param int $itemId
     * @param int $locationId
     * @return int|null
     */
    public function getItemStockForLocation(int $itemId, int $locationId): ?int;
    
    /**
     * Validate if items exist
     * 
     * @param array $itemIds
     * @return array Array of valid item IDs
     */
    public function validateItemIds(array $itemIds): array;
}