<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\In;

class StockAlertData extends BaseData
{
    public function __construct(
        #[Required, Numeric]
        public readonly int $itemId,
        
        public readonly ?int $variantId,
        
        public readonly ?int $locationId,
        
        #[Required]
        public readonly string $itemName,
        
        #[Required, Numeric]
        public readonly float $currentQuantity,
        
        #[Required, Numeric]
        public readonly float $minQuantity,
        
        #[Required, In(['low_stock', 'out_of_stock', 'replenished'])]
        public readonly string $alertType,
        
        public readonly ?float $suggestedReorderQuantity = null,
    ) {}
    
    /**
     * Get alert message
     */
    public function getAlertMessage(): string
    {
        return match($this->alertType) {
            'low_stock' => "Low stock alert for {$this->itemName}. Current: {$this->currentQuantity}, Minimum: {$this->minQuantity}",
            'out_of_stock' => "{$this->itemName} is out of stock",
            'replenished' => "{$this->itemName} has been replenished. Current: {$this->currentQuantity}",
            default => "Stock alert for {$this->itemName}",
        };
    }
    
    /**
     * Get alert severity
     */
    public function getAlertSeverity(): string
    {
        return match($this->alertType) {
            'out_of_stock' => 'critical',
            'low_stock' => 'warning',
            'replenished' => 'info',
            default => 'info',
        };
    }
    
    /**
     * Check if reorder is suggested
     */
    public function shouldReorder(): bool
    {
        return in_array($this->alertType, ['low_stock', 'out_of_stock']);
    }
}