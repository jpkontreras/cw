<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Carbon\Carbon;

class ItemsModified extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public array $addedItems,      // New items to add
        public array $removedItems,    // Item IDs to remove
        public array $modifiedItems,   // Items with quantity/modifier changes
        public string $modifiedBy,
        public string $reason,
        public Carbon $modifiedAt,
        public bool $requiresKitchenNotification = false,
        public int $previousTotal = 0,
        public int $newTotal = 0
    ) {
        $this->modifiedAt = $modifiedAt ?? now();
    }
    
    /**
     * Check if this modification adds new items
     */
    public function hasAdditions(): bool
    {
        return !empty($this->addedItems);
    }
    
    /**
     * Check if this modification removes items
     */
    public function hasRemovals(): bool
    {
        return !empty($this->removedItems);
    }
    
    /**
     * Check if this modification changes existing items
     */
    public function hasModifications(): bool
    {
        return !empty($this->modifiedItems);
    }
    
    /**
     * Get the total change in amount
     */
    public function getAmountDifference(): int
    {
        return $this->newTotal - $this->previousTotal;
    }
    
    /**
     * Check if this increases the order total
     */
    public function increasesTotal(): bool
    {
        return $this->getAmountDifference() > 0;
    }
}