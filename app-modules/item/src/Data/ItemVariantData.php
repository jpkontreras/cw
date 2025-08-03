<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ItemVariantData extends BaseData
{
    public function __construct(
        public readonly ?int $id,

        #[Required, Numeric]
        public readonly int $itemId,

        #[Required]
        public readonly string $name,

        public readonly ?string $sku,

        #[Numeric]
        public readonly float $priceAdjustment = 0,

        #[Numeric, Min(0.1)]
        public readonly float $sizeMultiplier = 1,

        public readonly bool $isDefault = false,

        public readonly bool $isActive = true,

        #[Numeric, Min(0)]
        public readonly int $stockQuantity = 0,

        #[Numeric, Min(0)]
        public readonly int $sortOrder = 0,

        public readonly ?Carbon $createdAt = null,

        public readonly ?Carbon $updatedAt = null,
    ) {}

    /**
     * Calculate the variant price based on base price
     */
    public function calculatePrice(float $basePrice): float
    {
        return $basePrice + $this->priceAdjustment;
    }

    /**
     * Get display name with size info if applicable
     */
    public function getDisplayName(): string
    {
        if ($this->sizeMultiplier !== 1.0) {
            return sprintf('%s (%.1fx)', $this->name, $this->sizeMultiplier);
        }

        return $this->name;
    }
}
