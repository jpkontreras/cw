<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class OrderSessionInitiated extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public ?string $userId,
        public string $locationId,
        public array $deviceInfo = [],
        public ?string $referrer = null,
        public array $metadata = []
    ) {}
}