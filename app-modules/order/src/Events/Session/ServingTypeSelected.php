<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ServingTypeSelected extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $servingType, // dine_in, takeout, delivery
        public ?string $previousType = null,
        public ?string $tableNumber = null,
        public ?string $deliveryAddress = null,
        public array $metadata = []
    ) {}
}