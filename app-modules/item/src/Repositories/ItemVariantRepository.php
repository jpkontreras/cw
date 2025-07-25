<?php

declare(strict_types=1);

namespace Colame\Item\Repositories;

use Colame\Item\Contracts\ItemVariantRepositoryInterface;
use Colame\Item\Data\ItemVariantData;
use Colame\Item\Models\ItemVariant;
use Illuminate\Support\Collection;

/**
 * Item variant repository implementation
 */
class ItemVariantRepository implements ItemVariantRepositoryInterface
{
    /**
     * Find a variant by ID
     */
    public function find(int $id): ?ItemVariantData
    {
        $variant = ItemVariant::find($id);
        
        return $variant ? ItemVariantData::from($variant) : null;
    }

    /**
     * Get all variants for an item
     */
    public function getByItem(int $itemId): Collection
    {
        return ItemVariant::where('item_id', $itemId)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn($variant) => ItemVariantData::from($variant));
    }

    /**
     * Create a new variant
     */
    public function create(array $data): ItemVariantData
    {
        $variant = ItemVariant::create($data);
        
        return ItemVariantData::from($variant);
    }

    /**
     * Update a variant
     */
    public function update(int $id, array $data): ?ItemVariantData
    {
        $variant = ItemVariant::find($id);
        
        if (!$variant) {
            return null;
        }
        
        $variant->update($data);
        
        return ItemVariantData::from($variant);
    }

    /**
     * Delete a variant
     */
    public function delete(int $id): bool
    {
        $variant = ItemVariant::find($id);
        
        if (!$variant) {
            return false;
        }
        
        return $variant->delete();
    }

    /**
     * Check variant availability
     */
    public function checkAvailability(int $id, int $quantity = 1): bool
    {
        $variant = ItemVariant::find($id);
        
        if (!$variant || !$variant->isAvailableForPurchase()) {
            return false;
        }
        
        if ($variant->current_stock !== null && $variant->current_stock < $quantity) {
            return false;
        }
        
        return true;
    }

    /**
     * Get variant price
     */
    public function getPrice(int $id, ?int $locationId = null): float
    {
        $variant = ItemVariant::with('item')->find($id);
        
        if (!$variant) {
            return 0.0;
        }
        
        $basePrice = $variant->item->base_price;
        
        // Check for location-specific pricing
        if ($locationId) {
            $pricing = $variant->item->pricing()->where('location_id', $locationId)->first();
            if ($pricing) {
                $basePrice = $pricing->getEffectivePrice();
            }
        }
        
        return $variant->getEffectivePrice($basePrice);
    }
}