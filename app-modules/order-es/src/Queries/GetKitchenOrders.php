<?php

declare(strict_types=1);

namespace Colame\OrderEs\Queries;

final readonly class GetKitchenOrders
{
    public function __construct(
        public int $locationId,
        public array $statuses = ['confirmed', 'preparing']
    ) {}
}