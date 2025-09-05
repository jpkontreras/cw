<?php

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class SessionAbandoned extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $reason, // timeout, navigation, browser_close, explicit
        public int $sessionDurationSeconds,
        public int $itemsInCart = 0,
        public float $cartValue = 0,
        public string $lastActivity = '',
        public array $metadata = []
    ) {}
}