<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class OrderDraftSaved extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public array $cartItems = [],
        public array $customerInfo = [],
        public ?string $servingType = null,
        public ?string $paymentMethod = null,
        public float $subtotal = 0,
        public bool $autoSaved = false,
        public array $metadata = []
    ) {}
}