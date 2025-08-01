<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;

class InventoryAdjustmentData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required, Numeric]
        public readonly int $itemId,
        
        public readonly ?int $variantId,
        
        public readonly ?int $locationId,
        
        #[Required, Numeric]
        public readonly float $quantityChange,
        
        #[Required]
        public readonly string $adjustmentType,
        
        #[Required]
        public readonly string $reason,
        
        public readonly ?string $notes,
        
        #[Numeric]
        public readonly float $beforeQuantity,
        
        #[Numeric]
        public readonly float $afterQuantity,
        
        public readonly ?int $userId,
        
        public readonly ?Carbon $createdAt = null,
    ) {}
    
    /**
     * Check if this is a positive adjustment
     */
    public function isPositive(): bool
    {
        return $this->quantityChange > 0;
    }
    
    /**
     * Check if this is a negative adjustment
     */
    public function isNegative(): bool
    {
        return $this->quantityChange < 0;
    }
    
    /**
     * Get adjustment type label
     */
    public function getAdjustmentTypeLabel(): string
    {
        return match($this->adjustmentType) {
            'recount' => 'Physical Recount',
            'damaged' => 'Damaged Goods',
            'expired' => 'Expired',
            'theft' => 'Theft/Loss',
            'return' => 'Return to Supplier',
            'stock_take' => 'Stock Take',
            'manual' => 'Manual Adjustment',
            default => ucfirst(str_replace('_', ' ', $this->adjustmentType)),
        };
    }
}