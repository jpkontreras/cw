<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PromotionsCalculated extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public array $availablePromotions,
        public array $autoApplied,
        public int $totalDiscount
    ) {}
}