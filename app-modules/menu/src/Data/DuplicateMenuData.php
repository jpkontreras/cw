<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class DuplicateMenuData extends BaseData
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,
    ) {}
}