<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Carbon\Carbon;

class ItemsAddedToOrder extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public array $items,
        public Carbon $timestamp
    ) {}
}