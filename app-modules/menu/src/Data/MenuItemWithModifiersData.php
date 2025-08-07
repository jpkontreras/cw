<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\MenuItem;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

class MenuItemWithModifiersData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $menuId,
        public readonly int $menuSectionId,
        public readonly int $itemId,
        public readonly ?string $displayName,
        public readonly ?string $displayDescription,
        public readonly ?float $priceOverride,
        public readonly bool $isActive,
        public readonly bool $isFeatured,
        public readonly bool $isRecommended,
        public readonly bool $isNew,
        public readonly bool $isSeasonal,
        public readonly int $sortOrder,
        public readonly ?int $preparationTimeOverride,
        
        #[DataCollectionOf(MenuItemModifierData::class)]
        public readonly Lazy|DataCollection $modifiers,
        
        public readonly ?array $dietaryLabels,
        public readonly ?array $allergenInfo,
        public readonly ?int $calorieCount,
        public readonly ?array $nutritionalInfo,
        public readonly ?string $imageUrl,
        public readonly ?array $metadata,
    ) {}
    
    public static function fromModel(MenuItem $item): self
    {
        return new self(
            id: $item->id,
            menuId: $item->menu_id,
            menuSectionId: $item->menu_section_id,
            itemId: $item->item_id,
            displayName: $item->display_name,
            displayDescription: $item->display_description,
            priceOverride: $item->price_override,
            isActive: $item->is_active,
            isFeatured: $item->is_featured,
            isRecommended: $item->is_recommended,
            isNew: $item->is_new,
            isSeasonal: $item->is_seasonal,
            sortOrder: $item->sort_order,
            preparationTimeOverride: $item->preparation_time_override,
            modifiers: Lazy::whenLoaded('modifiers', $item,
                fn() => MenuItemModifierData::collect($item->modifiers, DataCollection::class)
            ),
            dietaryLabels: $item->dietary_labels,
            allergenInfo: $item->allergen_info,
            calorieCount: $item->calorie_count,
            nutritionalInfo: $item->nutritional_info,
            imageUrl: $item->image_url,
            metadata: $item->metadata,
        );
    }
}