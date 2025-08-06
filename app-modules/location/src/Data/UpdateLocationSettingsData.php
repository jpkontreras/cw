<?php

declare(strict_types=1);

namespace Colame\Location\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;

class UpdateLocationSettingsData extends BaseData
{
    public function __construct(
        #[Required, ArrayType]
        public readonly array $settings,
    ) {}
}