<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class OrderCancelled extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $reason,
        public readonly int $cancelledBy,
        public readonly \DateTimeImmutable $cancelledAt
    ) {}
}