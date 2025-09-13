<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class DraftSaved extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly array $cartItems,
        public readonly array $customerInfo,
        public readonly ?string $servingType,
        public readonly ?string $paymentMethod,
        public readonly float $subtotal,
        public readonly bool $autoSaved,
        public readonly \DateTimeImmutable $savedAt
    ) {}
}