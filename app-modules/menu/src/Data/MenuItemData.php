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
        public readonly ?object $baseItem = null, // The actual item details from items table
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
        // Get display values from the item relationship if not overridden
        $displayName = $item->display_name ?: ($item->item ? $item->item->name : null);
        $displayDescription = $item->display_description ?: ($item->item ? $item->item->description : null);
        
        // Ensure price is properly cast to float or null
        $price = null;
        if ($item->price_override !== null) {
            $price = is_numeric($item->price_override) ? (float) $item->price_override : null;
        } elseif ($item->item && $item->item->base_price !== null) {
            $price = is_numeric($item->item->base_price) ? (float) $item->item->base_price : null;
        }
        
        $imageUrl = $item->image_url ?: ($item->item ? $item->item->image_url : null);
        
        return new self(
            id: $item->id,
            menuId: $item->menu_id,
            menuSectionId: $item->menu_section_id,
            itemId: $item->item_id,
            displayName: $displayName,
            displayDescription: $displayDescription,
            priceOverride: $price,
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
            imageUrl: $imageUrl,
            metadata: $item->metadata,
            createdAt: $item->created_at,
            updatedAt: $item->updated_at,
            baseItem: $item->item ? (object) [
                'name' => $item->item->name,
                'description' => $item->item->description,
                'basePrice' => (float) $item->item->base_price,
                'preparationTime' => $item->item->preparation_time,
                'category' => null, // TODO: Add category from taxonomy module
                'imageUrl' => null, // TODO: Add image handling
            ] : null,
        );
    }
}