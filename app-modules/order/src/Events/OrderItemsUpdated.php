<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class OrderItemsUpdated extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public array $updatedItems,
        public array $deletedItemIds,
        public int $previousTotal,
        public int $newTotal,
        public ?string $updatedBy = null,
        public ?\DateTimeInterface $updatedAt = null
    ) {
        $this->updatedAt = $this->updatedAt ?? now();
    }
    
    public function getAmountDifference(): int
    {
        return $this->newTotal - $this->previousTotal;
    }
}