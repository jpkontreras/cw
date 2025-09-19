<?php

declare(strict_types=1);

namespace Colame\Order\Queries;

final readonly class GetOrder
{
    public function __construct(
        public string $orderId
    ) {}
}