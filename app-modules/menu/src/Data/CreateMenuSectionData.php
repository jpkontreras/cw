<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Date;

class CreateMenuSectionData extends BaseData
{
    public function __construct(
        #[Required, IntegerType]
        public readonly int $menuId,
        
        #[Nullable, IntegerType]
        public readonly ?int $parentId = null,
        
        #[Required, StringType, Max(255)]
        public readonly string $name,
        
        #[Nullable, StringType, Max(500)]
        public readonly ?string $description = null,
        
        #[Nullable, StringType, Max(255)]
        public readonly ?string $displayName = null,
        
        #[Nullable, StringType, Max(50)]
        public readonly ?string $icon = null,
        
        #[BooleanType]
        public readonly bool $isActive = true,
        
        #[BooleanType]
        public readonly bool $isFeatured = false,
        
        #[IntegerType, Min(0)]
        public readonly int $sortOrder = 0,
        
        #[Nullable, Date]
        public readonly ?\DateTimeInterface $availableFrom = null,
        
        #[Nullable, Date]
        public readonly ?\DateTimeInterface $availableUntil = null,
        
        #[Nullable, ArrayType]
        public readonly ?array $availabilityDays = null,
        
        #[Nullable, Json]
        public readonly ?array $metadata = null,
    ) {}
}