<?php

declare(strict_types=1);

namespace Colame\Offer\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\After;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\LaravelData\Attributes\Validation\Unique;

class UpdateOfferData extends BaseData
{
    public function __construct(
        #[Max(100)]
        public readonly string|Optional $name,
        
        #[Max(255), Nullable]
        public readonly ?string|Optional $description,
        
        #[In(['percentage', 'fixed', 'buy_x_get_y', 'combo', 'happy_hour', 'early_bird', 'loyalty', 'staff'])]
        public readonly string|Optional $type,
        
        #[Min(0)]
        public readonly float|Optional $value,
        
        #[Min(0), Nullable]
        public readonly ?float|Optional $maxDiscount,
        
        #[Max(50), Nullable, Unique('offers', 'code', ignore: new RouteParameterReference('offer'))]
        public readonly ?string|Optional $code,
        
        public readonly bool|Optional $isActive,
        
        public readonly bool|Optional $autoApply,
        
        public readonly bool|Optional $isStackable,
        
        #[Date, Nullable]
        public readonly ?string|Optional $startsAt,
        
        #[Date, Nullable, After('startsAt')]
        public readonly ?string|Optional $endsAt,
        
        #[Nullable]
        public readonly ?string|Optional $recurringSchedule,
        
        #[ArrayType, Nullable]
        public readonly ?array|Optional $validDays,
        
        #[Nullable]
        public readonly ?string|Optional $validTimeStart,
        
        #[Nullable]
        public readonly ?string|Optional $validTimeEnd,
        
        #[Min(0), Nullable]
        public readonly ?float|Optional $minimumAmount,
        
        #[Min(1), Nullable]
        public readonly ?int|Optional $minimumQuantity,
        
        #[Min(1), Nullable]
        public readonly ?int|Optional $usageLimit,
        
        #[Min(1), Nullable]
        public readonly ?int|Optional $usagePerCustomer,
        
        #[Min(0), Max(100), Nullable]
        public readonly ?int|Optional $priority,
        
        #[ArrayType, Nullable]
        public readonly ?array|Optional $locationIds,
        
        #[ArrayType, Nullable]
        public readonly ?array|Optional $targetItemIds,
        
        #[ArrayType, Nullable]
        public readonly ?array|Optional $targetCategoryIds,
        
        #[ArrayType, Nullable]
        public readonly ?array|Optional $excludedItemIds,
        
        #[ArrayType, Nullable]
        public readonly ?array|Optional $customerSegments,
        
        #[ArrayType, Nullable]
        public readonly ?array|Optional $conditions,
        
        #[ArrayType, Nullable]
        public readonly ?array|Optional $metadata,
    ) {}
}