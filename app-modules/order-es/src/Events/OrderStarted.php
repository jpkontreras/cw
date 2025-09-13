<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class OrderStarted extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly int $userId, // This is the staff member, NOT a customer
        public readonly int $locationId,
        public readonly string $type,
        public readonly string $orderNumber,
        public readonly \DateTimeImmutable $startedAt,
        public readonly ?string $sessionId = null
    ) {}
}