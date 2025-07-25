<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * Update item data transfer object
 * 
 * Handles validation for item updates
 */
class UpdateItemData extends Data
{
    public function __construct(
        #[Sometimes, Max(255)]
        public readonly string|Optional $name,
        
        #[Sometimes, Max(100)]
        public readonly string|Optional $sku,
        
        #[Sometimes, Max(1000)]
        public readonly string|null|Optional $description,
        
        #[Sometimes, Min(0)]
        public readonly float|Optional $basePrice,
        
        #[Sometimes, Max(50)]
        public readonly string|null|Optional $unit,
        
        #[Sometimes]
        public readonly int|Optional $categoryId,
        
        #[Sometimes, In(['active', 'inactive', 'discontinued'])]
        public readonly string|Optional $status,
        
        #[Sometimes]
        public readonly bool|Optional $isAvailable,
        
        #[Sometimes]
        public readonly bool|Optional $trackInventory,
        
        #[Sometimes, Min(0)]
        public readonly int|null|Optional $lowStockThreshold,
        
        #[Sometimes]
        public readonly array|null|Optional $images,
        
        #[Sometimes]
        public readonly array|null|Optional $metadata,
    ) {}

    /**
     * Additional validation rules
     */
    public static function rules(): array
    {
        return [
            'sku' => ['sometimes', 'unique:items,sku'],
        ];
    }
}