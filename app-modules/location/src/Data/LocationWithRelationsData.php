<?php

declare(strict_types=1);

namespace Colame\Location\Data;

use App\Core\Data\BaseData;
use Colame\Location\Models\Location;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LocationWithRelationsData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
        public readonly string $address,
        public readonly string $city,
        public readonly ?string $state,
        public readonly string $country,
        public readonly ?string $postalCode,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly string $timezone,
        public readonly string $currency,
        public readonly ?array $openingHours,
        public readonly ?float $deliveryRadius,
        public readonly array $capabilities,
        public readonly ?int $parentLocationId,
        public readonly ?int $managerId,
        public readonly ?array $metadata,
        public readonly bool $isDefault,
        public readonly ?LocationData $parentLocation = null,
        #[DataCollectionOf(LocationData::class)]
        public readonly Lazy|DataCollection|null $childLocations = null,
        public readonly ?string $managerName = null,
        #[DataCollectionOf(LocationUserData::class)]
        public readonly Lazy|DataCollection|null $users = null,
        #[DataCollectionOf(LocationSettingsData::class)]
        public readonly Lazy|DataCollection|null $settings = null,
        public readonly ?int $totalUsers = null,
        public readonly ?int $activeOrders = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    /**
     * Create from Eloquent model with all relations
     */
    public static function fromModel(Location $location): self
    {
        return new self(
            id: $location->id,
            code: $location->code,
            name: $location->name,
            type: $location->type,
            status: $location->status,
            address: $location->address,
            city: $location->city,
            state: $location->state,
            country: $location->country,
            postalCode: $location->postal_code,
            phone: $location->phone,
            email: $location->email,
            timezone: $location->timezone,
            currency: $location->currency,
            openingHours: $location->opening_hours,
            deliveryRadius: $location->delivery_radius,
            capabilities: $location->capabilities ?? [],
            parentLocationId: $location->parent_location_id,
            managerId: $location->manager_id,
            metadata: $location->metadata,
            isDefault: $location->is_default,
            parentLocation: $location->relationLoaded('parentLocation') && $location->parentLocation 
                ? LocationData::fromModel($location->parentLocation) 
                : null,
            childLocations: Lazy::whenLoaded('childLocations', $location, 
                fn() => LocationData::collect($location->childLocations, DataCollection::class)
            ),
            managerName: $location->relationLoaded('manager') && $location->manager 
                ? $location->manager->name 
                : null,
            users: Lazy::whenLoaded('users', $location, 
                fn() => LocationUserData::collect($location->users, DataCollection::class)
            ),
            settings: Lazy::whenLoaded('settings', $location, 
                fn() => LocationSettingsData::collect($location->settings, DataCollection::class)
            ),
            totalUsers: $location->relationLoaded('users') 
                ? $location->users->count() 
                : null,
            activeOrders: null, // This would come from order module integration
            createdAt: $location->created_at,
            updatedAt: $location->updated_at,
        );
    }
}