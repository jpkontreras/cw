<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class MenuAvailabilityData extends BaseData
{
    public function __construct(
        public readonly int $menuId,
        public readonly bool $isCurrentlyAvailable,
        public readonly ?string $currentStatus,
        public readonly ?\DateTimeInterface $nextAvailableTime,
        public readonly ?array $todaySchedule,
        public readonly ?array $weekSchedule,
        
        #[DataCollectionOf(MenuAvailabilityRuleData::class)]
        public readonly ?DataCollection $activeRules,
        
        public readonly ?array $restrictions,
    ) {}
}