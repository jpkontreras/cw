<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\MenuItem;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Numeric;

class MenuItemData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required, IntegerType]
        public readonly int $menuId,
        #[Required, IntegerType]
        public readonly int $menuSectionId,
        #[Required, IntegerType]
        public readonly int $itemId,
        public readonly ?string $displayName,
        public readonly ?string $displayDescription,
        #[Numeric]
        public readonly ?float $priceOverride,
        public readonly bool $isActive = true,
        public readonly bool $isFeatured = false,
        public readonly bool $isRecommended = false,
        public readonly bool $isNew = false,
        public readonly bool $isSeasonal = false,
        public readonly int $sortOrder = 0,
        public readonly ?int $preparationTimeOverride = null,
        public readonly ?array $availableModifiers = null,
        public readonly ?array $dietaryLabels = null,
        public readonly ?array $allergenInfo = null,
        public readonly ?int $calorieCount = null,
        public readonly ?array $nutritionalInfo = null,
        public readonly ?string $imageUrl = null,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}
    
    #[Computed]
    public function badges(): array
    {
        $badges = [];
        
        if ($this->isFeatured) $badges[] = 'featured';
        if ($this->isRecommended) $badges[] = 'recommended';
        if ($this->isNew) $badges[] = 'new';
        if ($this->isSeasonal) $badges[] = 'seasonal';
        
        return $badges;
    }
    
    #[Computed]
    public function hasDietaryInfo(): bool
    {
        return !empty($this->dietaryLabels) || !empty($this->allergenInfo);
    }
    
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
            availableModifiers: $item->available_modifiers,
            dietaryLabels: $item->dietary_labels,
            allergenInfo: $item->allergen_info,
            calorieCount: $item->calorie_count,
            nutritionalInfo: $item->nutritional_info,
            imageUrl: $item->image_url,
            metadata: $item->metadata,
            createdAt: $item->created_at,
            updatedAt: $item->updated_at,
        );
    }
}