<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Carbon\Carbon;

class OrderCancelled extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $reason,
        public Carbon $cancelledAt
    ) {}
}