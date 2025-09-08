<?php

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;

class CreateOrderFlowData extends BaseData
{
    public function __construct(
        #[Required, Exists('staff', 'id')]
        public readonly string $staffId,
        
        #[Max(50)]
        public readonly ?string $tableNumber = null,
    ) {}
}