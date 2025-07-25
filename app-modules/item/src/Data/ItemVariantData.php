<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use App\Core\Data\BaseData;

/**
 * Item variant data transfer object
 * 
 * Represents product variants (size, color, etc.)
 */
class ItemVariantData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $itemId,
        public readonly string $name,
        public readonly string $sku,
        public readonly ?string $attributeType, // 'size', 'color', 'style', etc.
        public readonly ?string $attributeValue,
        public readonly float $priceAdjustment, // Added to base price
        public readonly ?float $weight,
        public readonly bool $isAvailable,
        public readonly bool $isDefault,
        public readonly ?int $currentStock,
        public readonly ?array $images,
        public readonly ?array $metadata,
    ) {}

    /**
     * Get the adjusted price for this variant
     */
    public function getAdjustedPrice(float $basePrice): float
    {
        return $basePrice + $this->priceAdjustment;
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        if ($this->currentStock === null) {
            return $this->isAvailable;
        }

        return $this->currentStock > 0;
    }
}