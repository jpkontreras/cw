<?php

declare(strict_types=1);

namespace Colame\OrderEs\Queries;

final readonly class GetOrder
{
    public function __construct(
        public string $orderId
    ) {}
}