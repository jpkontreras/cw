<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;

class PriceCalculationData extends BaseData
{
    public function __construct(
        public readonly int $itemId,
        
        public readonly ?int $variantId,
        
        public readonly ?int $locationId,
        
        public readonly float $basePrice,
        
        public readonly float $variantAdjustment = 0,
        
        #[DataCollectionOf(ModifierPriceImpactData::class)]
        public readonly array $modifierAdjustments = [],
        
        public readonly ?float $locationPrice = null,
        
        public readonly float $subtotal,
        
        public readonly float $total,
        
        public readonly string $currency = 'CLP',
        
        public readonly array $appliedRules = [],
    ) {}
    
    /**
     * Get total modifier adjustments
     */
    public function getTotalModifierAdjustment(): float
    {
        return array_reduce(
            $this->modifierAdjustments, 
            fn($sum, $adjustment) => $sum + $adjustment->priceImpact, 
            0
        );
    }
    
    /**
     * Check if location pricing was applied
     */
    public function hasLocationPricing(): bool
    {
        return $this->locationPrice !== null;
    }
    
    /**
     * Get the effective base price (with location override if applicable)
     */
    public function getEffectiveBasePrice(): float
    {
        return $this->locationPrice ?? $this->basePrice;
    }
    
    /**
     * Get price breakdown for display
     */
    public function getBreakdown(): array
    {
        $breakdown = [
            'Base Price' => $this->getEffectiveBasePrice(),
        ];
        
        if ($this->variantAdjustment !== 0.0) {
            $breakdown['Variant Adjustment'] = $this->variantAdjustment;
        }
        
        foreach ($this->modifierAdjustments as $adjustment) {
            $breakdown[$adjustment->modifierName] = $adjustment->priceImpact;
        }
        
        $breakdown['Total'] = $this->total;
        
        return $breakdown;
    }
}