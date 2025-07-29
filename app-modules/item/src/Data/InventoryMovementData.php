<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\In;

class InventoryMovementData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required]
        public readonly string $inventoriableType,
        
        #[Required, Numeric]
        public readonly int $inventoriableId,
        
        #[Required, Numeric]
        public readonly int $locationId,
        
        #[Required, In([
            'initial', 'purchase', 'sale', 'adjustment', 
            'transfer_in', 'transfer_out', 'waste', 'return', 'production'
        ])]
        public readonly string $movementType,
        
        #[Required, Numeric]
        public readonly float $quantity,
        
        public readonly ?float $unitCost,
        
        #[Required, Numeric]
        public readonly float $beforeQuantity,
        
        #[Required, Numeric]
        public readonly float $afterQuantity,
        
        public readonly ?string $referenceType,
        
        public readonly ?string $referenceId,
        
        public readonly ?string $reason,
        
        public readonly ?int $userId,
        
        public readonly ?Carbon $createdAt = null,
    ) {}
    
    /**
     * Check if this is a stock increase
     */
    public function isIncrease(): bool
    {
        return in_array($this->movementType, ['initial', 'purchase', 'transfer_in', 'return', 'production']);
    }
    
    /**
     * Check if this is a stock decrease
     */
    public function isDecrease(): bool
    {
        return in_array($this->movementType, ['sale', 'transfer_out', 'waste']);
    }
    
    /**
     * Get the absolute quantity change
     */
    public function getQuantityChange(): float
    {
        return abs($this->afterQuantity - $this->beforeQuantity);
    }
    
    /**
     * Get movement type label
     */
    public function getMovementTypeLabel(): string
    {
        return match($this->movementType) {
            'initial' => 'Initial Stock',
            'purchase' => 'Purchase',
            'sale' => 'Sale',
            'adjustment' => 'Stock Adjustment',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'waste' => 'Waste/Damage',
            'return' => 'Customer Return',
            'production' => 'Production',
            default => ucfirst($this->movementType),
        };
    }
}