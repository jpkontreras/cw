<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;

class MenuFilterData extends BaseData
{
    public function __construct(
        #[Nullable, IntegerType]
        public readonly ?int $locationId = null,
        
        #[Nullable, In(['regular', 'breakfast', 'lunch', 'dinner', 'event', 'seasonal'])]
        public readonly ?string $type = null,
        
        #[Nullable, BooleanType]
        public readonly ?bool $isActive = null,
        
        #[Nullable, BooleanType]
        public readonly ?bool $availableNow = null,
    ) {}
}