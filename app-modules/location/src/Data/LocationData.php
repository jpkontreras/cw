<?php

declare(strict_types=1);

namespace Colame\Location\Data;

use App\Core\Data\BaseData;
use Colame\Location\Models\Location;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LocationData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $address,
        public readonly ?string $city,
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
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    /**
     * Create from Eloquent model
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
            deliveryRadius: $location->delivery_radius ? (float) $location->delivery_radius : null,
            capabilities: $location->capabilities ?? [],
            parentLocationId: $location->parent_location_id,
            managerId: $location->manager_id,
            metadata: $location->metadata,
            isDefault: $location->is_default,
            parentLocation: $location->relationLoaded('parentLocation') && $location->parentLocation 
                ? self::fromModel($location->parentLocation) 
                : null,
            childLocations: Lazy::whenLoaded('childLocations', $location, 
                fn() => LocationData::collect($location->childLocations, DataCollection::class)
            ),
            managerName: $location->relationLoaded('manager') && $location->manager 
                ? $location->manager->name 
                : null,
            createdAt: $location->created_at,
            updatedAt: $location->updated_at,
        );
    }

    /**
     * Get full address
     */
    #[Computed]
    public function fullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get display name with code
     */
    #[Computed]
    public function displayName(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Check if location is active
     */
    #[Computed]
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if location has delivery capability
     */
    #[Computed]
    public function hasDelivery(): bool
    {
        return in_array('delivery', $this->capabilities);
    }

    /**
     * Check if location has dine-in capability
     */
    #[Computed]
    public function hasDineIn(): bool
    {
        return in_array('dine_in', $this->capabilities);
    }

    /**
     * Check if location has takeout capability
     */
    #[Computed]
    public function hasTakeout(): bool
    {
        return in_array('takeout', $this->capabilities);
    }

    /**
     * Check if location has catering capability
     */
    #[Computed]
    public function hasCatering(): bool
    {
        return in_array('catering', $this->capabilities);
    }

    /**
     * Get type label
     */
    #[Computed]
    public function typeLabel(): string
    {
        return match ($this->type) {
            'restaurant' => 'Restaurant',
            'kitchen' => 'Kitchen',
            'warehouse' => 'Warehouse',
            'central_kitchen' => 'Central Kitchen',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get status label
     */
    #[Computed]
    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'maintenance' => 'Under Maintenance',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    #[Computed]
    public function statusColor(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'maintenance' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Check if location is currently open
     */
    #[Computed]
    public function isOpen(): bool
    {
        if (!$this->openingHours) {
            return false;
        }

        $now = now()->setTimezone($this->timezone);
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        $hours = $this->openingHours[$dayOfWeek] ?? null;

        if (!$hours || !isset($hours['open']) || !isset($hours['close'])) {
            return false;
        }

        // Handle cases where closing time is after midnight
        if ($hours['close'] < $hours['open']) {
            return $currentTime >= $hours['open'] || $currentTime <= $hours['close'];
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    /**
     * Include computed properties in array transformation
     */
    public function with(): array
    {
        return [
            'displayName' => $this->displayName(),
            'isActive' => $this->isActive(),
            'statusLabel' => $this->statusLabel(),
            'statusColor' => $this->statusColor(),
            'isOpen' => $this->isOpen(),
        ];
    }
}