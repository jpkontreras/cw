<?php

declare(strict_types=1);

namespace Colame\Order\Data\Session;

use App\Core\Data\BaseData;
use Colame\Order\Enums\OrderPlatform;
use Colame\Order\Enums\OrderType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Ip;

class StartOrderFlowData extends BaseData
{
    public function __construct(
        #[Nullable]
        public readonly ?OrderPlatform $platform = null,
        
        #[Nullable, StringType]
        public readonly ?string $source = null,
        
        #[Nullable]
        public readonly ?OrderType $orderType = null,
        
        #[Nullable, StringType]
        public readonly ?string $referrer = null,
        
        #[Nullable, StringType]
        public readonly ?string $userAgent = null,
        
        #[Nullable, Ip]
        public readonly ?string $ipAddress = null,
    ) {}
    
}