<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\DataCollection;

class SaveMenuSectionData extends BaseData
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,
        
        #[DataCollectionOf(SaveMenuItemData::class)]
        public readonly DataCollection $items,
        
        #[Nullable, IntegerType]
        public readonly ?int $id = null,
        
        #[Nullable, StringType]
        public readonly ?string $description = null,
        
        #[Nullable, StringType]
        public readonly ?string $icon = null,
        
        #[BooleanType]
        #[MapInputName('isActive')]
        public readonly bool $isActive = true,
        
        #[BooleanType]
        #[MapInputName('isFeatured')]
        public readonly bool $isFeatured = false,
        
        #[BooleanType]
        #[MapInputName('isCollapsed')]
        public readonly ?bool $isCollapsed = false,
        
        #[IntegerType, Min(0)]
        #[MapInputName('sortOrder')]
        public readonly int $sortOrder = 0,
        
        #[DataCollectionOf(SaveMenuSectionData::class)]
        public readonly ?DataCollection $children = null,
    ) {}
}