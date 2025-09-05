<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CategoryBrowsed extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public int $categoryId,
        public string $categoryName,
        public int $itemsViewed = 0,
        public int $timeSpentSeconds = 0,
        public array $metadata = []
    ) {}
}