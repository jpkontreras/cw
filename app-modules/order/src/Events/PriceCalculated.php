<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PriceCalculated extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public int $subtotal,
        public int $discount,
        public int $tax,
        public int $tip,
        public int $total,
        public string $currency
    ) {}
}