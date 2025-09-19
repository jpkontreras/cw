<?php

declare(strict_types=1);

namespace Colame\Order\Commands;

final readonly class CancelOrder
{
    public function __construct(
        public string $orderId,
        public string $reason,
        public int $cancelledBy
    ) {}
}