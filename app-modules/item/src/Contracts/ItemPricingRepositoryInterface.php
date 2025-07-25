<?php

declare(strict_types=1);

namespace Colame\Item\Contracts;

use Colame\Item\Data\ItemPricingData;
use Illuminate\Support\Collection;

/**
 * Item pricing repository interface
 * 
 * Manages location-specific pricing for items
 */
interface ItemPricingRepositoryInterface
{
    /**
     * Find pricing by item and location
     * 
     * @param int $itemId
     * @param int $locationId
     * @return ItemPricingData|null
     */
    public function findByItemAndLocation(int $itemId, int $locationId): ?ItemPricingData;

    /**
     * Get all pricing for an item
     * 
     * @param int $itemId
     * @return Collection<ItemPricingData>
     */
    public function getByItem(int $itemId): Collection;

    /**
     * Get all pricing for a location
     * 
     * @param int $locationId
     * @return Collection<ItemPricingData>
     */
    public function getByLocation(int $locationId): Collection;

    /**
     * Create or update pricing
     * 
     * @param int $itemId
     * @param int $locationId
     * @param float $price
     * @param array $additionalData
     * @return ItemPricingData
     */
    public function upsert(int $itemId, int $locationId, float $price, array $additionalData = []): ItemPricingData;

    /**
     * Delete pricing
     * 
     * @param int $itemId
     * @param int $locationId
     * @return bool
     */
    public function delete(int $itemId, int $locationId): bool;

    /**
     * Get effective price for item at location
     * Falls back to base price if no location-specific pricing exists
     * 
     * @param int $itemId
     * @param int|null $locationId
     * @return float
     */
    public function getEffectivePrice(int $itemId, ?int $locationId = null): float;

    /**
     * Bulk update prices for a location
     * 
     * @param int $locationId
     * @param array $priceUpdates Array of [item_id => price]
     * @return int Number of updated records
     */
    public function bulkUpdateByLocation(int $locationId, array $priceUpdates): int;
}