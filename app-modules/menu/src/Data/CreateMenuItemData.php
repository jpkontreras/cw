<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
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

class CreateMenuItemData extends BaseData
{
    public function __construct(
        #[Required, IntegerType]
        public readonly int $menuId,
        
        #[Required, IntegerType]
        public readonly int $menuSectionId,
        
        #[Required, IntegerType]
        public readonly int $itemId,
        
        #[Nullable, StringType, Max(255)]
        public readonly ?string $displayName = null,
        
        #[Nullable, StringType, Max(1000)]
        public readonly ?string $displayDescription = null,
        
        #[Nullable, Numeric, Min(0)]
        public readonly ?float $priceOverride = null,
        
        #[BooleanType]
        public readonly bool $isActive = true,
        
        #[BooleanType]
        public readonly bool $isFeatured = false,
        
        #[BooleanType]
        public readonly bool $isRecommended = false,
        
        #[BooleanType]
        public readonly bool $isNew = false,
        
        #[BooleanType]
        public readonly bool $isSeasonal = false,
        
        #[IntegerType, Min(0)]
        public readonly int $sortOrder = 0,
        
        #[Nullable, IntegerType, Min(0), Max(1440)]
        public readonly ?int $preparationTimeOverride = null,
        
        #[Nullable, ArrayType]
        public readonly ?array $availableModifiers = null,
        
        #[Nullable, ArrayType]
        public readonly ?array $dietaryLabels = null,
        
        #[Nullable, ArrayType]
        public readonly ?array $allergenInfo = null,
        
        #[Nullable, IntegerType, Min(0)]
        public readonly ?int $calorieCount = null,
        
        #[Nullable, Json]
        public readonly ?array $nutritionalInfo = null,
        
        #[Nullable, Url]
        public readonly ?string $imageUrl = null,
        
        #[Nullable, Json]
        public readonly ?array $metadata = null,
    ) {}
}