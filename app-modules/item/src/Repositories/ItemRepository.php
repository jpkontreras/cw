<?php

declare(strict_types=1);

namespace Colame\Item\Repositories;

use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use Colame\Item\Data\ItemVariantData;
use Colame\Item\Data\ModifierGroupData;
use Colame\Item\Data\ItemModifierData;
use Colame\Item\Data\ItemPricingData;
use Colame\Item\Data\CategoryData;
use Colame\Item\Models\Item;
use Illuminate\Support\Collection;

/**
 * Item repository implementation
 */
class ItemRepository implements ItemRepositoryInterface
{
    /**
     * Find an item by ID
     */
    public function find(int $id): ?ItemData
    {
        $item = Item::find($id);
        
        return $item ? ItemData::from($item) : null;
    }

    /**
     * Find an item with all its relations
     */
    public function findWithRelations(int $id): ?ItemWithRelationsData
    {
        $item = Item::with(['variants', 'modifierGroups.modifiers', 'pricing'])->find($id);
        
        if (!$item) {
            return null;
        }

        $itemData = ItemData::from($item);
        
        $variants = $item->variants->map(fn($variant) => ItemVariantData::from($variant));
        
        $modifierGroups = $item->modifierGroups->map(function ($group) {
            $modifiers = $group->modifiers->map(fn($modifier) => ItemModifierData::from($modifier));
            return new ModifierGroupData(
                id: $group->id,
                name: $group->name,
                description: $group->description,
                type: $group->type,
                isRequired: $group->is_required,
                minSelections: $group->min_selections,
                maxSelections: $group->max_selections,
                sortOrder: $group->sort_order,
                modifiers: $modifiers,
            );
        });
        
        $locationPricing = $item->pricing->map(fn($pricing) => ItemPricingData::from($pricing));
        
        // TODO: Get category data from category module when available
        $category = null;
        
        return new ItemWithRelationsData(
            item: $itemData,
            variants: $variants,
            modifierGroups: $modifierGroups,
            locationPricing: $locationPricing,
            category: $category,
        );
    }

    /**
     * Get all items
     */
    public function all(): Collection
    {
        return Item::all()->map(fn($item) => ItemData::from($item));
    }

    /**
     * Get active items only
     */
    public function getActive(): Collection
    {
        return Item::where('status', Item::STATUS_ACTIVE)
            ->where('is_available', true)
            ->get()
            ->map(fn($item) => ItemData::from($item));
    }

    /**
     * Get items by category
     */
    public function getByCategory(int $categoryId): Collection
    {
        return Item::where('category_id', $categoryId)
            ->get()
            ->map(fn($item) => ItemData::from($item));
    }

    /**
     * Get items by location
     */
    public function getByLocation(int $locationId): Collection
    {
        // Get items that have pricing for this location
        $itemIds = \DB::table('item_pricing')
            ->where('location_id', $locationId)
            ->pluck('item_id');
        
        return Item::whereIn('id', $itemIds)
            ->get()
            ->map(fn($item) => ItemData::from($item));
    }

    /**
     * Create a new item
     */
    public function create(array $data): ItemData
    {
        $item = Item::create($data);
        
        return ItemData::from($item);
    }

    /**
     * Update an item
     */
    public function update(int $id, array $data): ?ItemData
    {
        $item = Item::find($id);
        
        if (!$item) {
            return null;
        }
        
        $item->update($data);
        
        return ItemData::from($item);
    }

    /**
     * Delete an item
     */
    public function delete(int $id): bool
    {
        $item = Item::find($id);
        
        if (!$item) {
            return false;
        }
        
        return $item->delete();
    }

    /**
     * Check if item is available
     */
    public function checkAvailability(int $id, int $quantity = 1): bool
    {
        $item = Item::find($id);
        
        if (!$item || !$item->isAvailableForPurchase()) {
            return false;
        }
        
        if ($item->track_inventory && $item->current_stock < $quantity) {
            return false;
        }
        
        return true;
    }

    /**
     * Get current price for item
     */
    public function getCurrentPrice(int $id, ?int $locationId = null): float
    {
        $item = Item::find($id);
        
        if (!$item) {
            return 0.0;
        }
        
        if ($locationId) {
            $pricing = $item->pricing()->where('location_id', $locationId)->first();
            if ($pricing) {
                return $pricing->getEffectivePrice();
            }
        }
        
        return $item->base_price;
    }

    /**
     * Search items by name or SKU
     */
    public function search(string $query): Collection
    {
        return Item::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('sku', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        })
        ->get()
        ->map(fn($item) => ItemData::from($item));
    }
}