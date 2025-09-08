<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class OrderStarted extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public ?string $staffId,
        public string $locationId,
        public ?string $tableNumber = null,
        public ?string $sessionId = null,
        public array $metadata = []
    ) {}
}