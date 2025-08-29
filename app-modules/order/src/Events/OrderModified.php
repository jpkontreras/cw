<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Carbon\Carbon;

class OrderModified extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public array $modifications,
        public string $modifiedBy,
        public Carbon $modifiedAt
    ) {}
}