<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class OrderConfirmed extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $paymentMethod,
        public readonly float $subtotal,
        public readonly float $tax,
        public readonly float $tip,
        public readonly float $total,
        public readonly \DateTimeImmutable $confirmedAt
    ) {}
}