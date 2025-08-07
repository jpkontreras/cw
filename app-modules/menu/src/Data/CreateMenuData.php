<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;

class CreateMenuData extends BaseData
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,
        
        #[StringType, Max(255), Unique('menus', 'slug')]
        public readonly ?string $slug = null,
        
        #[StringType, Max(1000)]
        public readonly ?string $description = null,
        
        #[Required, In(['regular', 'breakfast', 'lunch', 'dinner', 'event', 'seasonal'])]
        public readonly string $type = 'regular',
        
        public readonly bool $isActive = true,
        public readonly bool $isDefault = false,
        
        #[Min(0), Max(999)]
        public readonly int $sortOrder = 0,
        
        public readonly ?\DateTimeInterface $availableFrom = null,
        public readonly ?\DateTimeInterface $availableUntil = null,
        
        public readonly ?array $metadata = null,
        
        public readonly ?array $sections = null,
        public readonly ?array $locationIds = null,
    ) {}
}