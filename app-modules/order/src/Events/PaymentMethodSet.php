<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PaymentMethodSet extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $paymentMethod
    ) {}
}