<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class SessionConverted extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $orderId,
        public readonly \DateTimeImmutable $convertedAt
    ) {}
}