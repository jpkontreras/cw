<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class SlipScannedReady extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $orderNumber,
        public readonly \DateTimeImmutable $scannedAt
    ) {}
}