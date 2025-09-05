<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ItemAddedToCart extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public int $itemId,
        public string $itemName,
        public int $quantity,
        public float $unitPrice,
        public ?string $category = null,
        public array $modifiers = [],
        public ?string $notes = null,
        public string $addedFrom = 'browse', // browse, search, popular, recent, favorites
        public array $metadata = []
    ) {}
}