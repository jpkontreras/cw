<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class OrderStatusChanged extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly ?int $userId,
        public readonly ?string $reason,
        public readonly \DateTimeImmutable $changedAt
    ) {}
}