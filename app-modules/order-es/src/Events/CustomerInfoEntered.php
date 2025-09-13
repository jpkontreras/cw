<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class CustomerInfoEntered extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly array $fields,
        public readonly array $validationErrors,
        public readonly bool $isComplete,
        public readonly \DateTimeImmutable $enteredAt
    ) {}
}