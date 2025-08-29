<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PromotionRemoved extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $promotionId
    ) {}
}
