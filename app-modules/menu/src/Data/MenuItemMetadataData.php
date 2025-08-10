<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;

class MenuItemMetadataData extends BaseData
{
    public function __construct(
        public ?string $source = null,
        public ?string $origin = null,
        public ?string $servingSize = null,
        public ?string $servingSuggestion = null,
        public ?array $pairings = [],
        public ?array $tags = [],
        public ?string $chefNotes = null,
        public ?string $customBadge = null,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?\DateTimeInterface $availableFrom = null,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?\DateTimeInterface $availableUntil = null,
        public ?int $maxDailyOrders = null,
        public ?bool $requiresAdvanceOrder = false,
        public ?int $advanceOrderHours = null,
        public ?array $customFields = [],
    ) {}
}