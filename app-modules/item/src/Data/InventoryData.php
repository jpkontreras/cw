<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;

class InventoryData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required, Numeric]
        public readonly int $itemId,
        
        public readonly ?int $variantId,
        
        public readonly ?int $locationId,
        
        #[Numeric, Min(0)]
        public readonly float $quantityOnHand = 0,
        
        #[Numeric, Min(0)]
        public readonly float $quantityReserved = 0,
        
        #[Numeric, Min(0)]
        public readonly float $quantityAvailable = 0,
        
        #[Numeric, Min(0)]
        public readonly float $minQuantity = 0,
        
        #[Numeric, Min(0)]
        public readonly float $reorderQuantity = 0,
        
        public readonly ?float $maxQuantity = null,
        
        #[Numeric, Min(0)]
        public readonly float $unitCost = 0,
        
        public readonly ?Carbon $lastRestockedAt = null,
        
        public readonly ?Carbon $lastCountedAt = null,
    ) {}
    
    /**
     * Check if stock is below minimum
     */
    public function isBelowMinimum(): bool
    {
        return $this->quantityOnHand <= $this->minQuantity;
    }
    
    /**
     * Check if reorder is needed
     */
    public function needsReorder(): bool
    {
        return $this->isBelowMinimum();
    }
    
    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantityOnHand <= 0;
    }
    
    /**
     * Check if low stock
     */
    public function isLowStock(): bool
    {
        return $this->isBelowMinimum() && !$this->isOutOfStock();
    }
    
    /**
     * Get stock status
     */
    public function getStockStatus(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }
        
        if ($this->isLowStock()) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }
    
    /**
     * Get stock status label
     */
    public function getStockStatusLabel(): string
    {
        return match($this->getStockStatus()) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
        };
    }
    
    /**
     * Calculate reorder suggestion
     */
    public function calculateReorderSuggestion(): float
    {
        if (!$this->needsReorder()) {
            return 0;
        }
        
        // Suggest ordering enough to reach max quantity if set, otherwise double the reorder quantity
        if ($this->maxQuantity !== null) {
            return max(0, $this->maxQuantity - $this->quantityOnHand);
        }
        
        return $this->reorderQuantity > 0 ? $this->reorderQuantity : $this->minQuantity * 2;
    }
}