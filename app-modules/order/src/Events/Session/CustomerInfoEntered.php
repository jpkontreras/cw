<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CustomerInfoEntered extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public array $fields = [], // Array of field => value pairs
        public array $validationErrors = [],
        public bool $isComplete = false,
        public array $metadata = []
    ) {}
}