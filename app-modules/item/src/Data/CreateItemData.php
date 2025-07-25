<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

/**
 * Create item data transfer object
 * 
 * Handles validation for item creation
 */
class CreateItemData extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public readonly string $name,
        
        #[Required, Max(100), Unique('items', 'sku')]
        public readonly string $sku,
        
        #[Nullable, Max(1000)]
        public readonly ?string $description,
        
        #[Required, Min(0)]
        public readonly float $basePrice,
        
        #[Nullable, Max(50)]
        public readonly ?string $unit,
        
        #[Required]
        public readonly int $categoryId,
        
        #[Required, In(['simple', 'variant', 'compound'])]
        public readonly string $type = 'simple',
        
        #[Required, In(['active', 'inactive'])]
        public readonly string $status = 'active',
        
        public readonly bool $isAvailable = true,
        
        public readonly bool $trackInventory = false,
        
        #[Nullable, Min(0)]
        public readonly ?int $initialStock = null,
        
        #[Nullable, Min(0)]
        public readonly ?int $lowStockThreshold = null,
        
        #[Nullable]
        public readonly ?array $images = null,
        
        #[Nullable]
        public readonly ?array $metadata = null,
        
        // Location-specific pricing (optional during creation)
        #[Nullable]
        public readonly ?array $locationPricing = null,
        
        // Initial variants (for variant type items)
        #[Nullable]
        public readonly ?array $variants = null,
        
        // Initial modifier groups
        #[Nullable]
        public readonly ?array $modifierGroups = null,
    ) {}

    /**
     * Additional validation rules
     */
    public static function rules(): array
    {
        return [
            'locationPricing.*.location_id' => ['required', 'integer', 'exists:locations,id'],
            'locationPricing.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.sku' => ['required', 'string', 'max:100', 'unique:item_variants,sku'],
            'variants.*.price_adjustment' => ['nullable', 'numeric'],
            'modifierGroups.*.name' => ['required', 'string', 'max:255'],
            'modifierGroups.*.type' => ['required', 'in:single,multiple'],
            'modifierGroups.*.required' => ['boolean'],
            'modifierGroups.*.modifiers.*.name' => ['required', 'string', 'max:255'],
            'modifierGroups.*.modifiers.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }
}