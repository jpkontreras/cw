<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PaymentMethodSelected extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $paymentMethod, // cash, card, transfer, other
        public ?string $previousMethod = null,
        public array $metadata = []
    ) {}
}