<?php

namespace Colame\Item\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly int $itemId,
        public readonly ?int $variantId,
        public readonly ?int $locationId,
        public readonly float $previousQuantity,
        public readonly float $newQuantity,
    ) {}
    
    /**
     * Get the quantity change
     */
    public function getQuantityChange(): float
    {
        return $this->newQuantity - $this->previousQuantity;
    }
    
    /**
     * Check if stock decreased
     */
    public function isDecrease(): bool
    {
        return $this->newQuantity < $this->previousQuantity;
    }
    
    /**
     * Check if stock increased
     */
    public function isIncrease(): bool
    {
        return $this->newQuantity > $this->previousQuantity;
    }
}