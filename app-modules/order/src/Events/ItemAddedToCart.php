<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class ItemAddedToCart extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly int $quantity,
        public readonly float $basePrice,
        public readonly float $unitPrice,
        public readonly ?string $category,
        public readonly array $modifiers,
        public readonly ?string $notes,
        public readonly string $addedFrom,
        public readonly \DateTimeImmutable $addedAt
    ) {}
}