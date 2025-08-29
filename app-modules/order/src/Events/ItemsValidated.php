<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ItemsValidated extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public array $validatedItems,
        public int $subtotal,
        public string $currency
    ) {}
}