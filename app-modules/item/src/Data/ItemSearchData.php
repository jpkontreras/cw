<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;

class ItemSearchData extends BaseData
{
    public function __construct(
        public int $id,
        public string $name,
        public float $basePrice,
        public ?string $category,
        public ?string $description,
        public ?string $sku,
        public bool $isAvailable,
        public bool $isActive,
        public ?int $preparationTime,
        public ?int $stockQuantity,
        public ?string $image,
        public ?bool $isPopular = false,
        public ?int $orderFrequency = 0,
        public ?float $searchScore = null,
        public ?string $matchReason = null,
    ) {}
    
    #[Computed]
    public function formattedPrice(): string
    {
        return '$' . number_format($this->basePrice, 0);
    }
    
    #[Computed]
    public function inStock(): bool
    {
        return $this->stockQuantity === null || $this->stockQuantity > 0;
    }
    
    #[Computed]
    public function availability(): string
    {
        if (!$this->isActive) return 'inactive';
        if (!$this->isAvailable) return 'unavailable';
        if ($this->stockQuantity !== null && $this->stockQuantity <= 0) return 'out_of_stock';
        return 'available';
    }
}