<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Optional;

class UpdateMenuItemData extends BaseData
{
    public function __construct(
        #[IntegerType]
        public readonly int|Optional $itemId = new Optional(),
        
        #[StringType, Max(255)]
        public readonly string|null|Optional $displayName = new Optional(),
        
        #[StringType, Max(1000)]
        public readonly string|null|Optional $displayDescription = new Optional(),
        
        #[Numeric, Min(0)]
        public readonly float|null|Optional $priceOverride = new Optional(),
        
        #[BooleanType]
        public readonly bool|Optional $isActive = new Optional(),
        
        #[BooleanType]
        public readonly bool|Optional $isFeatured = new Optional(),
        
        #[BooleanType]
        public readonly bool|Optional $isRecommended = new Optional(),
        
        #[BooleanType]
        public readonly bool|Optional $isNew = new Optional(),
        
        #[BooleanType]
        public readonly bool|Optional $isSeasonal = new Optional(),
        
        #[IntegerType, Min(0)]
        public readonly int|Optional $sortOrder = new Optional(),
        
        #[IntegerType, Min(0), Max(1440)]
        public readonly int|null|Optional $preparationTimeOverride = new Optional(),
        
        #[ArrayType]
        public readonly array|null|Optional $availableModifiers = new Optional(),
        
        #[ArrayType]
        public readonly array|null|Optional $dietaryLabels = new Optional(),
        
        #[ArrayType]
        public readonly array|null|Optional $allergenInfo = new Optional(),
        
        #[IntegerType, Min(0)]
        public readonly int|null|Optional $calorieCount = new Optional(),
        
        #[Json]
        public readonly array|null|Optional $nutritionalInfo = new Optional(),
        
        #[Url]
        public readonly string|null|Optional $imageUrl = new Optional(),
        
        #[Json]
        public readonly array|null|Optional $metadata = new Optional(),
    ) {}
}