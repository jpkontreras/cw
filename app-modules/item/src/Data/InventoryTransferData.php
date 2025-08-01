<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;

class InventoryTransferData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required, Numeric]
        public readonly int $itemId,
        
        public readonly ?int $variantId,
        
        #[Required, Numeric]
        public readonly int $fromLocationId,
        
        #[Required, Numeric]
        public readonly int $toLocationId,
        
        #[Required, Numeric, Min(0.01)]
        public readonly float $quantity,
        
        public readonly ?string $notes,
        
        public readonly string $status = 'pending',
        
        public readonly ?string $transferId = null,
        
        public readonly ?int $initiatedBy,
        
        public readonly ?int $completedBy = null,
        
        public readonly ?Carbon $initiatedAt = null,
        
        public readonly ?Carbon $completedAt = null,
    ) {}
    
    /**
     * Check if transfer is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
    
    /**
     * Check if transfer is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
    
    /**
     * Check if transfer is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
    
    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'in_transit' => 'In Transit',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }
    
    /**
     * Get status badge variant
     */
    public function getStatusBadgeVariant(): string
    {
        return match($this->status) {
            'completed' => 'success',
            'cancelled' => 'destructive',
            'in_transit' => 'secondary',
            default => 'default',
        };
    }
}