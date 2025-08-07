<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\Menu;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Lazy;

class MenuData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required, StringType]
        public readonly string $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        #[In(['regular', 'breakfast', 'lunch', 'dinner', 'event', 'seasonal'])]
        public readonly string $type = 'regular',
        public readonly bool $isActive = true,
        public readonly bool $isDefault = false,
        public readonly int $sortOrder = 0,
        public readonly ?\DateTimeInterface $availableFrom = null,
        public readonly ?\DateTimeInterface $availableUntil = null,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}
    
    #[Computed]
    public function typeLabel(): string
    {
        return match($this->type) {
            'regular' => 'Regular Menu',
            'breakfast' => 'Breakfast Menu',
            'lunch' => 'Lunch Menu',
            'dinner' => 'Dinner Menu',
            'event' => 'Event Menu',
            'seasonal' => 'Seasonal Menu',
            default => 'Menu',
        };
    }
    
    #[Computed]
    public function isCurrentlyAvailable(): bool
    {
        if (!$this->isActive) {
            return false;
        }
        
        $now = now();
        
        if ($this->availableFrom && $now->lt($this->availableFrom)) {
            return false;
        }
        
        if ($this->availableUntil && $now->gt($this->availableUntil)) {
            return false;
        }
        
        return true;
    }
    
    public static function fromModel(Menu $menu): self
    {
        return new self(
            id: $menu->id,
            name: $menu->name,
            slug: $menu->slug,
            description: $menu->description,
            type: $menu->type,
            isActive: $menu->is_active,
            isDefault: $menu->is_default,
            sortOrder: $menu->sort_order,
            availableFrom: $menu->available_from,
            availableUntil: $menu->available_until,
            metadata: $menu->metadata,
            createdAt: $menu->created_at,
            updatedAt: $menu->updated_at,
        );
    }
}