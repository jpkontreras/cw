<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use App\Core\Data\BaseData;

/**
 * Item pricing data transfer object
 * 
 * Represents location-specific pricing for items
 */
class ItemPricingData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $itemId,
        public readonly int $locationId,
        public readonly float $price,
        public readonly ?float $costPrice,
        public readonly ?float $salePrice,
        public readonly bool $isOnSale,
        public readonly ?string $salePriceStartsAt,
        public readonly ?string $salePriceEndsAt,
        public readonly ?array $metadata,
    ) {}

    /**
     * Get the effective price (sale price if active, otherwise regular price)
     */
    public function getEffectivePrice(): float
    {
        if ($this->isOnSale && $this->salePrice !== null && $this->isSaleActive()) {
            return $this->salePrice;
        }

        return $this->price;
    }

    /**
     * Check if sale is currently active
     */
    public function isSaleActive(): bool
    {
        if (!$this->isOnSale) {
            return false;
        }

        $now = now();

        if ($this->salePriceStartsAt && $now->lt($this->salePriceStartsAt)) {
            return false;
        }

        if ($this->salePriceEndsAt && $now->gt($this->salePriceEndsAt)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate profit margin
     */
    public function getProfitMargin(): ?float
    {
        if ($this->costPrice === null || $this->costPrice === 0.0) {
            return null;
        }

        $effectivePrice = $this->getEffectivePrice();
        return (($effectivePrice - $this->costPrice) / $this->costPrice) * 100;
    }
}