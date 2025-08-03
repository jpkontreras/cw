<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ItemWithRelationsData extends BaseData
{
    public function __construct(
        public readonly ItemData $item,

        #[DataCollectionOf(ItemVariantData::class)]
        public readonly ?array $variants = null,

        #[DataCollectionOf(ModifierGroupWithModifiersData::class)]
        public readonly ?array $modifierGroups = null,

        #[DataCollectionOf(ItemImageData::class)]
        public readonly ?array $images = null,

        public readonly ?array $categories = null, // Array of category IDs or category data from taxonomy module

        public readonly ?array $tags = null, // Array of tag data from taxonomy module

        public readonly ?RecipeData $recipe = null,

        public readonly ?ItemLocationPriceData $currentPrice = null,

        public readonly ?array $childItems = null, // For compound items

        public readonly ?ItemLocationStockData $stockInfo = null,
    ) {}

    /**
     * Get the primary image
     */
    public function getPrimaryImage(): ?ItemImageData
    {
        if (!$this->images) {
            return null;
        }

        foreach ($this->images as $image) {
            if ($image->isPrimary) {
                return $image;
            }
        }

        // Return first image if no primary
        return $this->images[0] ?? null;
    }

    /**
     * Get the default variant
     */
    public function getDefaultVariant(): ?ItemVariantData
    {
        if (!$this->variants) {
            return null;
        }

        foreach ($this->variants as $variant) {
            if ($variant->isDefault) {
                return $variant;
            }
        }

        // Return first variant if no default
        return $this->variants[0] ?? null;
    }

    /**
     * Get the current effective price
     */
    public function getEffectivePrice(): float
    {
        if ($this->currentPrice && $this->currentPrice->isCurrentlyValid()) {
            return $this->currentPrice->price;
        }

        return $this->item->basePrice;
    }

    /**
     * Check if item has modifiers
     */
    public function hasModifiers(): bool
    {
        return !empty($this->modifierGroups);
    }

    /**
     * Check if item has variants
     */
    public function hasVariants(): bool
    {
        return !empty($this->variants) && count($this->variants) > 1;
    }

    /**
     * Get all required modifier groups
     */
    public function getRequiredModifierGroups(): array
    {
        if (!$this->modifierGroups) {
            return [];
        }

        return array_filter($this->modifierGroups, fn($group) => $group->modifierGroup->isRequired);
    }
}
