<?php

declare(strict_types=1);

namespace Colame\Order\Commands;

use Illuminate\Support\Str;

final readonly class StartOrder
{
    public string $orderId;
    
    public function __construct(
        public int $customerId,
        public int $locationId,
        public string $type = 'dine_in', // dine_in, takeaway, delivery
        ?string $orderId = null
    ) {
        $this->orderId = $orderId ?? (string) Str::uuid();
    }
}