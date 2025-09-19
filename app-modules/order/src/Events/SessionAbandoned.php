<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class SessionAbandoned extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $reason,
        public readonly int $sessionDurationSeconds,
        public readonly int $itemsInCart,
        public readonly float $cartValue,
        public readonly string $lastActivity,
        public readonly \DateTimeImmutable $abandonedAt
    ) {}
}