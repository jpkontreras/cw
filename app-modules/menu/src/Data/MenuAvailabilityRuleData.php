<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\MenuAvailabilityRule;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IntegerType;

class MenuAvailabilityRuleData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required, IntegerType]
        public readonly int $menuId,
        #[Required, In(['time_based', 'day_based', 'date_range', 'capacity_based'])]
        public readonly string $ruleType,
        public readonly ?array $daysOfWeek,
        public readonly ?string $startTime,
        public readonly ?string $endTime,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?int $minCapacity,
        public readonly ?int $maxCapacity,
        public readonly bool $isRecurring = false,
        public readonly ?string $recurrencePattern = null,
        public readonly int $priority = 0,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}
    
    public static function fromModel(MenuAvailabilityRule $rule): self
    {
        return new self(
            id: $rule->id,
            menuId: $rule->menu_id,
            ruleType: $rule->rule_type,
            daysOfWeek: $rule->days_of_week,
            startTime: $rule->start_time?->format('H:i'),
            endTime: $rule->end_time?->format('H:i'),
            startDate: $rule->start_date?->format('Y-m-d'),
            endDate: $rule->end_date?->format('Y-m-d'),
            minCapacity: $rule->min_capacity,
            maxCapacity: $rule->max_capacity,
            isRecurring: $rule->is_recurring,
            recurrencePattern: $rule->recurrence_pattern,
            priority: $rule->priority,
            metadata: $rule->metadata,
            createdAt: $rule->created_at,
            updatedAt: $rule->updated_at,
        );
    }
}