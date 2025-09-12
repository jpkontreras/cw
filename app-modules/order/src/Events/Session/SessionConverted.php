<?php

declare(strict_types=1);

namespace Colame\Order\Events\Session;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class SessionConverted extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $orderId,
        public ?string $paymentMethod = null,
        public ?string $customerName = null,
        public ?string $customerPhone = null,
        public ?string $customerEmail = null,
        public ?string $notes = null,
        public array $metadata = []
    ) {}
}