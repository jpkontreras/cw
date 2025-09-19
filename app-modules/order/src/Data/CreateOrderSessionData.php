<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use Colame\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\In;

class CreateOrderSessionData extends BaseData
{
    public function __construct(
        #[Required]
        #[In(['web', 'mobile', 'api'])]
        public readonly string $platform,

        #[Required]
        #[In(['web', 'mobile', 'pos', 'kiosk'])]
        public readonly string $source,

        #[Required]
        #[In(['dine_in', 'takeout', 'delivery'])]
        public readonly string $orderType,

        public readonly ?int $locationId = null,
        public readonly ?int $staffId = null,
    ) {}
}