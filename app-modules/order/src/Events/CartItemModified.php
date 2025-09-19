<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class CartItemModified extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly string $modificationType,
        public readonly array $changes,
        public readonly \DateTimeImmutable $modifiedAt
    ) {}
}