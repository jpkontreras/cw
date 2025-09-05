<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ItemSearched extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $query,
        public array $filters = [],
        public int $resultsCount = 0,
        public ?string $searchId = null,
        public array $metadata = []
    ) {}
}