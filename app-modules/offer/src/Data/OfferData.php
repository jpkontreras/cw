<?php

declare(strict_types=1);

namespace Colame\Offer\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

class OfferData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required, Max(100)]
        public readonly string $name,
        
        #[Max(255)]
        public readonly ?string $description,
        
        #[Required, In(['percentage', 'fixed', 'buy_x_get_y', 'combo', 'happy_hour', 'early_bird', 'loyalty', 'staff'])]
        public readonly string $type,
        
        #[Required, Min(0)]
        public readonly float $value,
        
        public readonly ?float $maxDiscount,
        
        #[Max(50)]
        public readonly ?string $code,
        
        #[Required]
        public readonly bool $isActive,
        
        public readonly bool $autoApply,
        
        public readonly bool $isStackable,
        
        public readonly ?Carbon $startsAt,
        
        public readonly ?Carbon $endsAt,
        
        public readonly ?string $recurringSchedule,
        
        public readonly ?array $validDays,
        
        public readonly ?string $validTimeStart,
        
        public readonly ?string $validTimeEnd,
        
        public readonly ?float $minimumAmount,
        
        public readonly ?int $minimumQuantity,
        
        public readonly ?int $usageLimit,
        
        public readonly ?int $usagePerCustomer,
        
        public readonly int $usageCount,
        
        public readonly ?int $priority,
        
        public readonly ?array $locationIds,
        
        public readonly ?array $targetItemIds,
        
        public readonly ?array $targetCategoryIds,
        
        public readonly ?array $excludedItemIds,
        
        public readonly ?array $customerSegments,
        
        public readonly ?array $conditions,
        
        public readonly ?array $metadata,
        
        public readonly ?Carbon $createdAt,
        
        public readonly ?Carbon $updatedAt,
        
        public Lazy|DataCollection $usageHistory,
        
        public Lazy|DataCollection $appliedOffers,
    ) {}
    
    #[Computed]
    public function isValid(): bool
    {
        if (!$this->isActive) {
            return false;
        }
        
        $now = Carbon::now();
        
        if ($this->startsAt && $now->isBefore($this->startsAt)) {
            return false;
        }
        
        if ($this->endsAt && $now->isAfter($this->endsAt)) {
            return false;
        }
        
        if ($this->usageLimit && $this->usageCount >= $this->usageLimit) {
            return false;
        }
        
        return true;
    }
    
    #[Computed]
    public function formattedValue(): string
    {
        return match($this->type) {
            'percentage' => $this->value . '%',
            'fixed' => '$' . number_format($this->value, 2),
            'buy_x_get_y' => $this->formatBuyXGetY(),
            default => (string) $this->value,
        };
    }
    
    #[Computed]
    public function remainingUses(): ?int
    {
        if (!$this->usageLimit) {
            return null;
        }
        
        return max(0, $this->usageLimit - $this->usageCount);
    }
    
    #[Computed]
    public function statusLabel(): string
    {
        if (!$this->isActive) {
            return 'Inactive';
        }
        
        if ($this->startsAt && Carbon::now()->isBefore($this->startsAt)) {
            return 'Scheduled';
        }
        
        if ($this->endsAt && Carbon::now()->isAfter($this->endsAt)) {
            return 'Expired';
        }
        
        if ($this->usageLimit && $this->usageCount >= $this->usageLimit) {
            return 'Exhausted';
        }
        
        return 'Active';
    }
    
    private function formatBuyXGetY(): string
    {
        $buyQty = $this->conditions['buy_quantity'] ?? 0;
        $getQty = $this->conditions['get_quantity'] ?? 0;
        return "Buy {$buyQty} Get {$getQty}";
    }
    
    public static function fromModel($offer): self
    {
        return new self(
            id: $offer->id,
            name: $offer->name,
            description: $offer->description,
            type: $offer->type,
            value: $offer->value,
            maxDiscount: $offer->max_discount,
            code: $offer->code,
            isActive: $offer->is_active,
            autoApply: $offer->auto_apply,
            isStackable: $offer->is_stackable,
            startsAt: $offer->starts_at ? Carbon::parse($offer->starts_at) : null,
            endsAt: $offer->ends_at ? Carbon::parse($offer->ends_at) : null,
            recurringSchedule: $offer->recurring_schedule,
            validDays: $offer->valid_days,
            validTimeStart: $offer->valid_time_start,
            validTimeEnd: $offer->valid_time_end,
            minimumAmount: $offer->minimum_amount,
            minimumQuantity: $offer->minimum_quantity,
            usageLimit: $offer->usage_limit,
            usagePerCustomer: $offer->usage_per_customer,
            usageCount: $offer->usage_count ?? 0,
            priority: $offer->priority,
            locationIds: $offer->location_ids,
            targetItemIds: $offer->target_item_ids,
            targetCategoryIds: $offer->target_category_ids,
            excludedItemIds: $offer->excluded_item_ids,
            customerSegments: $offer->customer_segments,
            conditions: $offer->conditions,
            metadata: $offer->metadata,
            createdAt: $offer->created_at ? Carbon::parse($offer->created_at) : null,
            updatedAt: $offer->updated_at ? Carbon::parse($offer->updated_at) : null,
            usageHistory: Lazy::whenLoaded('usageHistory', $offer, 
                fn() => OfferUsageData::collect($offer->usageHistory, DataCollection::class)
            ),
            appliedOffers: Lazy::whenLoaded('appliedOffers', $offer,
                fn() => AppliedOfferData::collect($offer->appliedOffers, DataCollection::class)
            ),
        );
    }
}