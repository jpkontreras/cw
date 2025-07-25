<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;

/**
 * Item data transfer object
 * 
 * Represents core item information for external consumption
 */
class ItemData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $sku,
        public readonly ?string $description,
        public readonly float $basePrice,
        public readonly ?string $unit,
        public readonly int $categoryId,
        public readonly string $type, // 'simple', 'variant', 'compound'
        public readonly string $status, // 'active', 'inactive', 'discontinued'
        public readonly bool $isAvailable,
        public readonly bool $trackInventory,
        public readonly ?int $currentStock,
        public readonly ?int $lowStockThreshold,
        public readonly ?array $images,
        public readonly ?array $metadata,
        #[Computed]
        public readonly ?string $displayPrice = null,
    ) {}

    /**
     * Get formatted display price
     */
    public function displayPrice(): string
    {
        return '$' . number_format($this->basePrice, 2);
    }

    /**
     * Check if item is in stock
     */
    public function isInStock(): bool
    {
        if (!$this->trackInventory) {
            return $this->isAvailable;
        }

        return $this->currentStock > 0;
    }

    /**
     * Check if item is low on stock
     */
    public function isLowStock(): bool
    {
        if (!$this->trackInventory || !$this->lowStockThreshold) {
            return false;
        }

        return $this->currentStock <= $this->lowStockThreshold;
    }
}