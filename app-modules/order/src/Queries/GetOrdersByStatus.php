<?php

declare(strict_types=1);

namespace Colame\Order\Queries;

final readonly class GetOrdersByStatus
{
    public function __construct(
        public array $statuses,
        public ?int $locationId = null,
        public int $perPage = 20
    ) {}
}