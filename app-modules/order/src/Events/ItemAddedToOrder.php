<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class ItemAddedToOrder extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $lineItemId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly float $basePrice,
        public readonly float $unitPrice,
        public readonly int $quantity,
        public readonly array $modifiers,
        public readonly ?string $notes
    ) {}
}