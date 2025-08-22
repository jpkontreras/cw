<?php

declare(strict_types=1);

namespace Colame\Offer\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;

class DiscountCalculationData extends BaseData
{
    public function __construct(
        public readonly int $offerId,
        public readonly string $offerName,
        public readonly string $calculationType,
        public readonly float $originalAmount,
        public readonly float $discountAmount,
        public readonly float $finalAmount,
        public readonly ?array $affectedItems,
        public readonly ?array $calculationDetails,
        public readonly bool $wasLimited,
        public readonly ?string $limitReason,
    ) {}
    
    #[Computed]
    public function savingsPercentage(): float
    {
        if ($this->originalAmount == 0) {
            return 0;
        }
        
        return round(($this->discountAmount / $this->originalAmount) * 100, 2);
    }
    
    #[Computed]
    public function isValid(): bool
    {
        return $this->discountAmount > 0 && $this->finalAmount >= 0;
    }
}