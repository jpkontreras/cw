<?php

declare(strict_types=1);

namespace Colame\Offer\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\After;
use Spatie\LaravelData\Attributes\Validation\Unique;

class CreateOfferData extends BaseData
{
    public function __construct(
        #[Required, Max(100)]
        public readonly string $name,
        
        #[Max(255), Nullable]
        public readonly ?string $description,
        
        #[Required, In(['percentage', 'fixed', 'buy_x_get_y', 'combo', 'happy_hour', 'early_bird', 'loyalty', 'staff'])]
        public readonly string $type,
        
        #[Required, Min(0)]
        public readonly float $value,
        
        #[Min(0), Nullable]
        public readonly ?float $maxDiscount,
        
        #[Max(50), Nullable, Unique('offers', 'code')]
        public readonly ?string $code,
        
        public readonly bool $isActive = true,
        
        public readonly bool $autoApply = false,
        
        public readonly bool $isStackable = false,
        
        #[Date, Nullable]
        public readonly ?string $startsAt,
        
        #[Date, Nullable, After('startsAt')]
        public readonly ?string $endsAt,
        
        #[Nullable]
        public readonly ?string $recurringSchedule,
        
        #[ArrayType, Nullable]
        public readonly ?array $validDays,
        
        #[Nullable]
        public readonly ?string $validTimeStart,
        
        #[Nullable]
        public readonly ?string $validTimeEnd,
        
        #[Min(0), Nullable]
        public readonly ?float $minimumAmount,
        
        #[Min(1), Nullable]
        public readonly ?int $minimumQuantity,
        
        #[Min(1), Nullable]
        public readonly ?int $usageLimit,
        
        #[Min(1), Nullable]
        public readonly ?int $usagePerCustomer,
        
        #[Min(0), Max(100), Nullable]
        public readonly ?int $priority,
        
        #[ArrayType, Nullable]
        public readonly ?array $locationIds,
        
        #[ArrayType, Nullable]
        public readonly ?array $targetItemIds,
        
        #[ArrayType, Nullable]
        public readonly ?array $targetCategoryIds,
        
        #[ArrayType, Nullable]
        public readonly ?array $excludedItemIds,
        
        #[ArrayType, Nullable]
        public readonly ?array $customerSegments,
        
        #[ArrayType, Nullable]
        public readonly ?array $conditions,
        
        #[ArrayType, Nullable]
        public readonly ?array $metadata,
    ) {}
}