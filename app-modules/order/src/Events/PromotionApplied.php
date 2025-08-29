<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PromotionApplied extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $promotionId,
        public int $discountAmount,
        public string $currency
    ) {}
}
