<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;

class AvailabilityCheckData extends BaseData
{
    public function __construct(
        #[Nullable, IntegerType]
        public readonly ?int $locationId = null,
        
        #[Nullable, Date, WithCast(DateTimeInterfaceCast::class)]
        public readonly ?\DateTimeInterface $datetime = null,
    ) {}
    
    public function getDateTime(): \DateTimeInterface
    {
        return $this->datetime ?? now();
    }
}