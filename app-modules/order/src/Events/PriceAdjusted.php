<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Carbon\Carbon;

class PriceAdjusted extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $adjustmentType,  // 'discount', 'surcharge', 'correction', 'tip'
        public int $amount,              // Amount in cents (CLP)
        public string $currency,
        public string $reason,
        public string $authorizedBy,     // User who authorized the adjustment
        public bool $affectsPayment,     // Whether this requires payment reconciliation
        public Carbon $adjustedAt,
        public ?string $authorizationCode = null,  // For manager overrides
        public array $metadata = []
    ) {
        $this->adjustedAt = $adjustedAt ?? now();
        $this->currency = $currency ?? 'CLP';
    }
    
    /**
     * Check if this is a discount
     */
    public function isDiscount(): bool
    {
        return $this->adjustmentType === 'discount';
    }
    
    /**
     * Check if this is a surcharge
     */
    public function isSurcharge(): bool
    {
        return $this->adjustmentType === 'surcharge';
    }
    
    /**
     * Check if this requires manager authorization
     */
    public function requiresAuthorization(): bool
    {
        // Discounts over 20% or any correction requires authorization
        return $this->adjustmentType === 'correction' || 
               ($this->isDiscount() && $this->getDiscountPercentage() > 20);
    }
    
    /**
     * Get discount percentage (if applicable)
     */
    public function getDiscountPercentage(): ?float
    {
        if (!$this->isDiscount() || !isset($this->metadata['original_total'])) {
            return null;
        }
        
        $originalTotal = $this->metadata['original_total'];
        if ($originalTotal <= 0) {
            return 0;
        }
        
        return ($this->amount / $originalTotal) * 100;
    }
    
    /**
     * Check if this adjustment was authorized
     */
    public function isAuthorized(): bool
    {
        return !empty($this->authorizationCode);
    }
}