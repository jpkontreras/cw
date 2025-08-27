<?php

declare(strict_types=1);

namespace Colame\Business\Data;

use App\Core\Data\BaseData;
use Colame\Business\Models\BusinessSubscription;
use DateTime;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BusinessSubscriptionData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $businessId,
        public readonly string $planId,
        public readonly string $planName,
        public readonly float $price,
        public readonly string $currency,
        public readonly string $billingCycle,
        public readonly string $status,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly DateTime $startsAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $endsAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $cancelledAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $trialEndsAt,
        public readonly ?string $paymentMethod,
        public readonly ?array $paymentMetadata,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $lastPaymentAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $nextPaymentAt,
        public readonly ?array $usageLimits,
        public readonly ?array $currentUsage,
        public readonly ?array $metadata,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly DateTime $createdAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly DateTime $updatedAt,
    ) {}

    #[Computed]
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    #[Computed]
    public function isOnTrial(): bool
    {
        return $this->trialEndsAt && $this->trialEndsAt > new DateTime();
    }

    #[Computed]
    public function daysUntilRenewal(): ?int
    {
        if (!$this->nextPaymentAt) {
            return null;
        }

        $now = new DateTime();
        $diff = $now->diff($this->nextPaymentAt);
        
        return $diff->days;
    }

    public static function from(mixed ...$payloads): static
    {
        if (count($payloads) === 1 && $payloads[0] instanceof BusinessSubscription) {
            return self::fromModel($payloads[0]);
        }
        
        return parent::from(...$payloads);
    }
    
    public static function fromModel(BusinessSubscription $subscription): self
    {
        return new self(
            id: $subscription->id,
            businessId: $subscription->business_id,
            planId: $subscription->plan_id,
            planName: $subscription->plan_name,
            price: $subscription->price,
            currency: $subscription->currency,
            billingCycle: $subscription->billing_cycle,
            status: $subscription->status,
            startsAt: $subscription->starts_at,
            endsAt: $subscription->ends_at,
            cancelledAt: $subscription->cancelled_at,
            trialEndsAt: $subscription->trial_ends_at,
            paymentMethod: $subscription->payment_method,
            paymentMetadata: $subscription->payment_metadata,
            lastPaymentAt: $subscription->last_payment_at,
            nextPaymentAt: $subscription->next_payment_at,
            usageLimits: $subscription->usage_limits,
            currentUsage: $subscription->current_usage,
            metadata: $subscription->metadata,
            createdAt: $subscription->created_at,
            updatedAt: $subscription->updated_at,
        );
    }
}