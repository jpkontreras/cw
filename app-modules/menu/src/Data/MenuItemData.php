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
    
    /**
     * Create from Eloquent model
     * 
     * @param MenuItem $item The menu item model
     * @param object|null $itemDetails Optional item details from item module (passed via service layer)
     */
    public static function fromModel(MenuItem $item, ?object $itemDetails = null): self
    {
        // Use display overrides if set, otherwise use provided item details
        $displayName = $item->display_name ?: ($itemDetails?->name ?? null);
        $displayDescription = $item->display_description ?: ($itemDetails?->description ?? null);
        
        // Ensure price is properly cast to float or null
        $price = null;
        if ($item->price_override !== null) {
            $price = is_numeric($item->price_override) ? (float) $item->price_override : null;
        } elseif ($itemDetails && isset($itemDetails->basePrice)) {
            $price = is_numeric($itemDetails->basePrice) ? (float) $itemDetails->basePrice : null;
        }
        
        // Use image override if set, otherwise use provided item details
        $imageUrl = $item->image_url ?: ($itemDetails?->imageUrl ?? null);
        
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
            baseItem: $itemDetails ? (object) [
                'name' => $itemDetails->name ?? null,
                'description' => $itemDetails->description ?? null,
                'basePrice' => isset($itemDetails->basePrice) ? (float) $itemDetails->basePrice : null,
                'preparationTime' => $itemDetails->preparationTime ?? null,
                'category' => $itemDetails->category ?? null,
                'imageUrl' => $itemDetails->imageUrl ?? null,
            ] : null,
        );
    }
    
    /**
     * Magic creation method for Laravel-data compatibility
     * This ensures that collect() and other automatic conversions work properly
     */
    public static function from(mixed ...$payloads): static
    {
        // If the first payload is a MenuItem model, use our custom fromModel method
        if (count($payloads) === 1 && $payloads[0] instanceof MenuItem) {
            return static::fromModel($payloads[0], null);
        }
        
        // Otherwise, use the parent implementation
        return parent::from(...$payloads);
    }
}