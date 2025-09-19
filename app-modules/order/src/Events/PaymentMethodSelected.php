<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PaymentMethodSelected extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $paymentMethod,
        public readonly ?string $previousMethod,
        public readonly \DateTimeImmutable $selectedAt
    ) {}
}