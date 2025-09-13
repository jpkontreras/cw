<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events\SessionEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class SessionInitiated extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly ?int $staffId,
        public readonly int $locationId,
        public readonly string $type,
        public readonly ?int $tableNumber,
        public readonly int $customerCount,
        public readonly array $metadata
    ) {}
}