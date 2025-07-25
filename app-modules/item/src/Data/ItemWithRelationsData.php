<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Illuminate\Support\Collection;

/**
 * Item with relations data transfer object
 * 
 * Includes variants, modifiers, and pricing information
 */
class ItemWithRelationsData extends BaseData
{
    public function __construct(
        public readonly ItemData $item,
        /** @var Collection<ItemVariantData> */
        public readonly Collection $variants,
        /** @var Collection<ModifierGroupData> */
        public readonly Collection $modifierGroups,
        /** @var Collection<ItemPricingData> */
        public readonly Collection $locationPricing,
        public readonly ?CategoryData $category = null,
    ) {}

    /**
     * Get all available modifiers
     */
    public function getAllModifiers(): Collection
    {
        return $this->modifierGroups->flatMap(fn($group) => $group->modifiers);
    }

    /**
     * Get price for specific location
     */
    public function getPriceForLocation(int $locationId): float
    {
        $pricing = $this->locationPricing->firstWhere('locationId', $locationId);
        
        return $pricing ? $pricing->price : $this->item->basePrice;
    }

    /**
     * Check if item has variants
     */
    public function hasVariants(): bool
    {
        return $this->variants->isNotEmpty();
    }

    /**
     * Check if item has modifiers
     */
    public function hasModifiers(): bool
    {
        return $this->modifierGroups->isNotEmpty();
    }
}