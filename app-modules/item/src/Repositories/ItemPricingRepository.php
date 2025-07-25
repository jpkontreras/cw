<?php

declare(strict_types=1);

namespace Colame\Item\Repositories;

use Colame\Item\Contracts\ItemPricingRepositoryInterface;
use Colame\Item\Data\ItemPricingData;
use Colame\Item\Models\Item;
use Colame\Item\Models\ItemPricing;
use Illuminate\Support\Collection;

/**
 * Item pricing repository implementation
 */
class ItemPricingRepository implements ItemPricingRepositoryInterface
{
    /**
     * Find pricing by item and location
     */
    public function findByItemAndLocation(int $itemId, int $locationId): ?ItemPricingData
    {
        $pricing = ItemPricing::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->first();
        
        return $pricing ? ItemPricingData::from($pricing) : null;
    }

    /**
     * Get all pricing for an item
     */
    public function getByItem(int $itemId): Collection
    {
        return ItemPricing::where('item_id', $itemId)
            ->get()
            ->map(fn($pricing) => ItemPricingData::from($pricing));
    }

    /**
     * Get all pricing for a location
     */
    public function getByLocation(int $locationId): Collection
    {
        return ItemPricing::where('location_id', $locationId)
            ->get()
            ->map(fn($pricing) => ItemPricingData::from($pricing));
    }

    /**
     * Create or update pricing
     */
    public function upsert(int $itemId, int $locationId, float $price, array $additionalData = []): ItemPricingData
    {
        $data = array_merge([
            'price' => $price,
        ], $additionalData);
        
        $pricing = ItemPricing::updateOrCreate(
            [
                'item_id' => $itemId,
                'location_id' => $locationId,
            ],
            $data
        );
        
        return ItemPricingData::from($pricing);
    }

    /**
     * Delete pricing
     */
    public function delete(int $itemId, int $locationId): bool
    {
        return ItemPricing::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->delete() > 0;
    }

    /**
     * Get effective price for item at location
     */
    public function getEffectivePrice(int $itemId, ?int $locationId = null): float
    {
        if ($locationId) {
            $pricing = ItemPricing::where('item_id', $itemId)
                ->where('location_id', $locationId)
                ->first();
            
            if ($pricing) {
                return $pricing->getEffectivePrice();
            }
        }
        
        // Fall back to base price
        $item = Item::find($itemId);
        return $item ? $item->base_price : 0.0;
    }

    /**
     * Bulk update prices for a location
     */
    public function bulkUpdateByLocation(int $locationId, array $priceUpdates): int
    {
        $updated = 0;
        
        foreach ($priceUpdates as $itemId => $price) {
            $result = ItemPricing::updateOrCreate(
                [
                    'item_id' => $itemId,
                    'location_id' => $locationId,
                ],
                [
                    'price' => $price,
                    'updated_at' => now(),
                ]
            );
            
            if ($result->wasRecentlyCreated || $result->wasChanged()) {
                $updated++;
            }
        }
        
        return $updated;
    }
}