<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class OrderStatusTransitioned extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $previousStatus,
        public string $newStatus,
        public ?string $reason = null,
        public ?string $transitionedBy = null,
        public ?\DateTimeInterface $transitionedAt = null
    ) {
        $this->transitionedAt = $this->transitionedAt ?? now();
    }
}