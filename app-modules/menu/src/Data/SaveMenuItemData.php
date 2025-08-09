<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class SaveMenuItemData extends BaseData
{
    public function __construct(
        #[Required, IntegerType]
        #[MapInputName('itemId')]
        public readonly int $itemId,
        
        #[Nullable, IntegerType]
        public readonly ?int $id = null,
        
        #[Nullable, StringType]
        #[MapInputName('displayName')]
        public readonly ?string $displayName = null,
        
        #[Nullable, StringType]
        #[MapInputName('displayDescription')]
        public readonly ?string $displayDescription = null,
        
        #[Nullable, Numeric, Min(0)]
        #[MapInputName('priceOverride')]
        public readonly ?float $priceOverride = null,
        
        #[BooleanType]
        #[MapInputName('isFeatured')]
        public readonly bool $isFeatured = false,
        
        #[BooleanType]
        #[MapInputName('isRecommended')]
        public readonly bool $isRecommended = false,
        
        #[BooleanType]
        #[MapInputName('isNew')]
        public readonly bool $isNew = false,
        
        #[BooleanType]
        #[MapInputName('isSeasonal')]
        public readonly bool $isSeasonal = false,
        
        #[IntegerType, Min(0)]
        #[MapInputName('sortOrder')]
        public readonly int $sortOrder = 0,
        
        // The baseItem is provided from frontend for display purposes
        // but not used in save operation
        #[MapInputName('baseItem')]
        public readonly ?array $baseItem = null,
    ) {}
}