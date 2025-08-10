<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\Menu;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

class MenuWithRelationsData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        public readonly string $type,
        public readonly bool $isActive,
        public readonly bool $isDefault,
        public readonly int $sortOrder,
        public readonly ?\DateTimeInterface $availableFrom,
        public readonly ?\DateTimeInterface $availableUntil,
        public readonly ?array $metadata,
        
        #[DataCollectionOf(MenuSectionWithItemsData::class)]
        public readonly Lazy|DataCollection $sections,
        
        #[DataCollectionOf(MenuItemData::class)]
        public readonly Lazy|DataCollection $items,
        
        #[DataCollectionOf(MenuAvailabilityRuleData::class)]
        public readonly Lazy|DataCollection $availabilityRules,
        
        #[DataCollectionOf(MenuLocationData::class)]
        public readonly Lazy|DataCollection $locations,
        
        public readonly ?\DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $updatedAt,
    ) {}
    
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
            sections: Lazy::whenLoaded('sections', $menu,
                fn() => MenuSectionWithItemsData::collect($menu->sections, DataCollection::class)
            ),
            items: Lazy::whenLoaded('items', $menu,
                fn() => MenuItemData::collect($menu->items, DataCollection::class)
            ),
            availabilityRules: Lazy::whenLoaded('availabilityRules', $menu,
                fn() => MenuAvailabilityRuleData::collect($menu->availabilityRules, DataCollection::class)
            ),
            locations: Lazy::whenLoaded('locations', $menu,
                fn() => MenuLocationData::collect($menu->locations, DataCollection::class)
            ),
            createdAt: $menu->created_at,
            updatedAt: $menu->updated_at,
        );
    }
}