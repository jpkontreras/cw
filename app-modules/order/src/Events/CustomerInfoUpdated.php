<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CustomerInfoUpdated extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public ?string $customerName = null,
        public ?string $customerPhone = null,
        public ?string $customerEmail = null,
        public ?string $deliveryAddress = null,
        public ?string $tableNumber = null,
        public ?string $notes = null,
        public ?string $specialInstructions = null,
        public ?string $updatedBy = null,
        public ?\DateTimeInterface $updatedAt = null
    ) {
        $this->updatedAt = $this->updatedAt ?? now();
    }
}