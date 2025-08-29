<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Carbon\Carbon;

class OrderConfirmed extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $orderNumber,
        public Carbon $confirmedAt
    ) {}
}