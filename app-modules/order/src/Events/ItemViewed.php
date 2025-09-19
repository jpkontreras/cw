<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class ItemViewed extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly float $price,
        public readonly ?string $category,
        public readonly string $viewSource,
        public readonly int $viewDurationSeconds,
        public readonly \DateTimeImmutable $viewedAt
    ) {}
}