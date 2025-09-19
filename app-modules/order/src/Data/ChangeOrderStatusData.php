<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use Colame\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\In;

class ChangeOrderStatusData extends BaseData
{
    public function __construct(
        #[Required]
        #[In(['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'])]
        public readonly string $status,

        public readonly ?string $notes = null,
    ) {}
}