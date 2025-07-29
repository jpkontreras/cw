<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;

class ItemLocationStockData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required, Numeric]
        public readonly int $itemId,
        
        public readonly ?int $itemVariantId,
        
        #[Required, Numeric]
        public readonly int $locationId,
        
        #[Numeric, Min(0)]
        public readonly float $quantity = 0,
        
        #[Numeric, Min(0)]
        public readonly float $reservedQuantity = 0,
        
        #[Numeric, Min(0)]
        public readonly float $reorderPoint = 0,
        
        #[Numeric, Min(0)]
        public readonly float $reorderQuantity = 0,
        
        public readonly ?Carbon $createdAt = null,
        
        public readonly ?Carbon $updatedAt = null,
    ) {}
    
    /**
     * Get available quantity (total minus reserved)
     */
    public function getAvailableQuantity(): float
    {
        return max(0, $this->quantity - $this->reservedQuantity);
    }
    
    /**
     * Check if stock is available for given quantity
     */
    public function hasAvailableStock(float $requestedQuantity): bool
    {
        return $this->getAvailableQuantity() >= $requestedQuantity;
    }
    
    /**
     * Check if reorder is needed
     */
    public function needsReorder(): bool
    {
        return $this->quantity <= $this->reorderPoint;
    }
    
    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }
    
    /**
     * Check if low stock (below reorder point but not out)
     */
    public function isLowStock(): bool
    {
        return $this->needsReorder() && !$this->isOutOfStock();
    }
    
    /**
     * Get stock status label
     */
    public function getStockStatus(): string
    {
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        }
        
        if ($this->isLowStock()) {
            return 'Low Stock';
        }
        
        return 'In Stock';
    }
}