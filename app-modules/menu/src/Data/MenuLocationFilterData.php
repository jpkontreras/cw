<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;

class MenuLocationFilterData extends BaseData
{
    public function __construct(
        #[Nullable, IntegerType]
        public readonly ?int $locationId = null,
    ) {}
}