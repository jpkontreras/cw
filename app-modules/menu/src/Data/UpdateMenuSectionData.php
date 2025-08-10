<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Optional;

class UpdateMenuSectionData extends BaseData
{
    public function __construct(
        #[IntegerType]
        public readonly int|null|Optional $parentId = new Optional(),
        
        #[StringType, Max(255)]
        public readonly string|Optional $name = new Optional(),
        
        #[StringType, Max(500)]
        public readonly string|null|Optional $description = new Optional(),
        
        #[StringType, Max(255)]
        public readonly string|null|Optional $displayName = new Optional(),
        
        #[StringType, Max(50)]
        public readonly string|null|Optional $icon = new Optional(),
        
        #[BooleanType]
        public readonly bool|Optional $isActive = new Optional(),
        
        #[BooleanType]
        public readonly bool|Optional $isFeatured = new Optional(),
        
        #[IntegerType, Min(0)]
        public readonly int|Optional $sortOrder = new Optional(),
        
        #[Date]
        public readonly \DateTimeInterface|null|Optional $availableFrom = new Optional(),
        
        #[Date]
        public readonly \DateTimeInterface|null|Optional $availableUntil = new Optional(),
        
        #[ArrayType]
        public readonly array|null|Optional $availabilityDays = new Optional(),
        
        #[Json]
        public readonly array|null|Optional $metadata = new Optional(),
    ) {}
}