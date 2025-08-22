<?php

declare(strict_types=1);

namespace Colame\Offer\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Computed;

class AppliedOfferData extends BaseData
{
    public function __construct(
        public readonly int $offerId,
        public readonly string $offerName,
        public readonly string $offerType,
        public readonly float $originalAmount,
        public readonly float $discountAmount,
        public readonly float $finalAmount,
        public readonly ?string $code,
        public readonly ?int $orderId,
        public readonly ?int $customerId,
        public readonly ?array $appliedToItems,
        public readonly Carbon $appliedAt,
    ) {}
    
    #[Computed]
    public function discountPercentage(): float
    {
        if ($this->originalAmount == 0) {
            return 0;
        }
        
        return round(($this->discountAmount / $this->originalAmount) * 100, 2);
    }
    
    #[Computed]
    public function formattedDiscount(): string
    {
        return '$' . number_format($this->discountAmount, 2);
    }
    
    #[Computed]
    public function formattedOriginal(): string
    {
        return '$' . number_format($this->originalAmount, 2);
    }
    
    #[Computed]
    public function formattedFinal(): string
    {
        return '$' . number_format($this->finalAmount, 2);
    }
}