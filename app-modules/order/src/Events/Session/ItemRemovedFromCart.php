<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ItemRemovedFromCart extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public int $itemId,
        public string $itemName,
        public int $removedQuantity,
        public string $removalReason = 'user_action', // user_action, out_of_stock, price_change
        public array $metadata = []
    ) {}
}