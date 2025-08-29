<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TipAdded extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public int $tipAmount,
        public string $currency
    ) {}
}