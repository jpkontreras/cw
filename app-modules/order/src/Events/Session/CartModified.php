<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CartModified extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public int $itemId,
        public string $itemName,
        public string $modificationType, // quantity_changed, notes_updated, modifiers_changed
        public array $changes = [],
        public array $metadata = []
    ) {}
}