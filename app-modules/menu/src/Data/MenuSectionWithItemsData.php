<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\MenuSection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

class MenuSectionWithItemsData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $menuId,
        public readonly ?int $parentId,
        public readonly string $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        public readonly ?string $displayName,
        public readonly bool $isActive,
        public readonly bool $isFeatured,
        public readonly int $sortOrder,
        public readonly bool $isAvailable,
        
        #[DataCollectionOf(MenuItemData::class)]
        public readonly Lazy|DataCollection $items,
        
        #[DataCollectionOf(MenuSectionWithItemsData::class)]
        public readonly Lazy|DataCollection $children,
        
        public readonly ?array $metadata,
    ) {}
    
    public static function fromModel(MenuSection $section): self
    {
        return new self(
            id: $section->id,
            menuId: $section->menu_id,
            parentId: $section->parent_id,
            name: $section->name,
            slug: $section->slug,
            description: $section->description,
            displayName: $section->display_name,
            isActive: $section->is_active,
            isFeatured: $section->is_featured,
            sortOrder: $section->sort_order,
            isAvailable: $section->isAvailable(),
            items: Lazy::whenLoaded('activeItems', $section,
                fn() => MenuItemData::collect($section->activeItems, DataCollection::class)
            ),
            children: Lazy::whenLoaded('children', $section,
                fn() => self::collect($section->children, DataCollection::class)
            ),
            metadata: $section->metadata,
        );
    }
}