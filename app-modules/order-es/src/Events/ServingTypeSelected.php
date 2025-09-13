<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class ServingTypeSelected extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $servingType,
        public readonly ?string $previousType,
        public readonly ?string $tableNumber,
        public readonly ?string $deliveryAddress,
        public readonly \DateTimeImmutable $selectedAt
    ) {}
}