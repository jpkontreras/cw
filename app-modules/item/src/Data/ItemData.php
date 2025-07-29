<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\In;

class ItemData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required]
        public readonly string $name,
        
        public readonly string $slug,
        
        public readonly ?string $description,
        
        public readonly ?string $sku,
        
        public readonly ?string $barcode,
        
        #[Required, Numeric, Min(0)]
        public readonly float $basePrice,
        
        #[Numeric, Min(0)]
        public readonly float $baseCost = 0,
        
        #[Numeric, Min(0)]
        public readonly int $preparationTime = 0,
        
        public readonly bool $isActive = true,
        
        public readonly bool $isAvailable = true,
        
        public readonly bool $isFeatured = false,
        
        public readonly bool $trackInventory = false,
        
        #[Numeric, Min(0)]
        public readonly int $stockQuantity = 0,
        
        #[Numeric, Min(0)]
        public readonly int $lowStockThreshold = 10,
        
        #[In(['single', 'compound'])]
        public readonly string $itemType = 'single',
        
        public readonly ?array $allergens = null,
        
        public readonly ?array $nutritionalInfo = null,
        
        #[Numeric, Min(0)]
        public readonly int $sortOrder = 0,
        
        public readonly ?Carbon $availableFrom = null,
        
        public readonly ?Carbon $availableUntil = null,
        
        public readonly ?Carbon $createdAt = null,
        
        public readonly ?Carbon $updatedAt = null,
        
        public readonly ?Carbon $deletedAt = null,
    ) {}
    
    /**
     * Check if the item is currently available based on time constraints
     */
    public function isCurrentlyAvailable(): bool
    {
        if (!$this->isAvailable || !$this->isActive) {
            return false;
        }
        
        $now = now();
        
        if ($this->availableFrom && $now->isBefore($this->availableFrom)) {
            return false;
        }
        
        if ($this->availableUntil && $now->isAfter($this->availableUntil)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if the item needs reorder
     */
    public function needsReorder(): bool
    {
        return $this->trackInventory && $this->stockQuantity <= $this->lowStockThreshold;
    }
    
    /**
     * Get the profit margin
     */
    public function getProfitMargin(): float
    {
        if ($this->baseCost <= 0) {
            return 0;
        }
        
        return (($this->basePrice - $this->baseCost) / $this->baseCost) * 100;
    }
}