<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class ItemRemovedFromCart extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly int $removedQuantity,
        public readonly string $removalReason,
        public readonly \DateTimeImmutable $removedAt
    ) {}
}