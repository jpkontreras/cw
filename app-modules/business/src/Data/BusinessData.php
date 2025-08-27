<?php

declare(strict_types=1);

namespace Colame\Business\Data;

use App\Core\Data\BaseData;
use Colame\Business\Enums\BusinessStatus;
use Colame\Business\Enums\BusinessType;
use Colame\Business\Enums\SubscriptionTier;
use Colame\Business\Models\Business;
use DateTime;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BusinessData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $legalName,
        public readonly ?string $taxId,
        public readonly BusinessType $type,
        public readonly BusinessStatus $status,
        public readonly int $ownerId,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $website,
        public readonly ?string $address,
        public readonly ?string $addressLine2,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly string $country,
        public readonly ?string $postalCode,
        public readonly string $currency,
        public readonly string $timezone,
        public readonly string $locale,
        public readonly ?array $settings,
        public readonly SubscriptionTier $subscriptionTier,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $trialEndsAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly ?DateTime $subscriptionEndsAt,
        public readonly ?array $features,
        public readonly ?array $limits,
        public readonly ?string $logoUrl,
        public readonly ?string $primaryColor,
        public readonly ?string $secondaryColor,
        public readonly ?array $metadata,
        public readonly bool $isDemo,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly DateTime $createdAt,
        #[WithCast(DateTimeInterfaceCast::class)]
        public readonly DateTime $updatedAt,
        public readonly Lazy|DataCollection $users,
        public readonly Lazy|DataCollection $locations,
        public readonly Lazy|BusinessSubscriptionData $currentSubscription,
    ) {}

    #[Computed]
    public function isActive(): bool
    {
        return $this->status === BusinessStatus::ACTIVE;
    }

    #[Computed]
    public function isOnTrial(): bool
    {
        return $this->trialEndsAt && $this->trialEndsAt > new DateTime();
    }

    #[Computed]
    public function hasActiveSubscription(): bool
    {
        return !$this->subscriptionEndsAt || $this->subscriptionEndsAt > new DateTime();
    }

    #[Computed]
    public function fullAddress(): ?string
    {
        if (!$this->address) {
            return null;
        }

        $parts = array_filter([
            $this->address,
            $this->addressLine2,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public static function from(mixed ...$payloads): static
    {
        if (count($payloads) === 1 && $payloads[0] instanceof Business) {
            return self::fromModel($payloads[0]);
        }
        
        return parent::from(...$payloads);
    }
    
    public static function fromModel(Business $business): self
    {
        return new self(
            id: $business->id,
            name: $business->name,
            slug: $business->slug,
            legalName: $business->legal_name,
            taxId: $business->tax_id,
            type: BusinessType::from($business->type),
            status: BusinessStatus::from($business->status),
            ownerId: $business->owner_id,
            email: $business->email,
            phone: $business->phone,
            website: $business->website,
            address: $business->address,
            addressLine2: $business->address_line_2,
            city: $business->city,
            state: $business->state,
            country: $business->country,
            postalCode: $business->postal_code,
            currency: $business->currency,
            timezone: $business->timezone,
            locale: $business->locale,
            settings: $business->settings,
            subscriptionTier: SubscriptionTier::from($business->subscription_tier),
            trialEndsAt: $business->trial_ends_at,
            subscriptionEndsAt: $business->subscription_ends_at,
            features: $business->features,
            limits: $business->limits,
            logoUrl: $business->logo_url,
            primaryColor: $business->primary_color,
            secondaryColor: $business->secondary_color,
            metadata: $business->metadata,
            isDemo: $business->is_demo,
            createdAt: $business->created_at,
            updatedAt: $business->updated_at,
            users: Lazy::whenLoaded('users', $business, fn() => 
                BusinessUserData::collect($business->users, DataCollection::class)
            ),
            locations: Lazy::whenLoaded('locations', $business, fn() => 
                DataCollection::empty() // Will be LocationData::collection when Location module is updated
            ),
            currentSubscription: Lazy::whenLoaded('currentSubscription', $business, fn() => 
                $business->currentSubscription 
                    ? BusinessSubscriptionData::fromModel($business->currentSubscription)
                    : null
            ),
        );
    }
}