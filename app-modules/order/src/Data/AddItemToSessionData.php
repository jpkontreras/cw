<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use Colame\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Min;

class AddItemToSessionData extends BaseData
{
    public function __construct(
        #[Required]
        public readonly int $itemId,

        #[Required]
        #[Min(1)]
        public readonly int $quantity,

        public readonly ?string $notes = null,
        public readonly ?array $modifiers = null,
    ) {}
}