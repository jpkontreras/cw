<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class CategoryBrowsed extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $categoryId,
        public readonly string $categoryName,
        public readonly int $itemsViewed,
        public readonly int $timeSpentSeconds,
        public readonly \DateTimeImmutable $browsedAt
    ) {}
}