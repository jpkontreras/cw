<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Optional;

class UpdateMenuData extends BaseData
{
    public function __construct(
        #[StringType, Max(255)]
        public readonly string|Optional $name = new Optional(),
        
        #[StringType, Max(255)]
        public readonly string|Optional $slug = new Optional(),
        
        #[StringType, Max(1000)]
        public readonly string|null|Optional $description = new Optional(),
        
        #[In(['regular', 'breakfast', 'lunch', 'dinner', 'event', 'seasonal'])]
        public readonly string|Optional $type = new Optional(),
        
        public readonly bool|Optional $isActive = new Optional(),
        public readonly bool|Optional $isDefault = new Optional(),
        
        #[Min(0), Max(999)]
        public readonly int|Optional $sortOrder = new Optional(),
        
        public readonly \DateTimeInterface|null|Optional $availableFrom = new Optional(),
        public readonly \DateTimeInterface|null|Optional $availableUntil = new Optional(),
        
        public readonly array|null|Optional $metadata = new Optional(),
    ) {}
}