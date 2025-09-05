<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ItemViewed extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public int $itemId,
        public string $itemName,
        public float $price,
        public ?string $category = null,
        public string $viewSource = 'browse', // browse, search, popular, recent
        public int $viewDurationSeconds = 0,
        public array $metadata = []
    ) {}
}