<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class ItemSearched extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $query,
        public readonly array $filters,
        public readonly int $resultsCount,
        public readonly ?string $searchId,
        public readonly \DateTimeImmutable $searchedAt
    ) {}
}