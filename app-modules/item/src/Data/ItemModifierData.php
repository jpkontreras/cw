<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ItemModifierData extends BaseData
{
    public function __construct(
        public readonly ?int $id,

        #[Required, Numeric]
        public readonly int $modifierGroupId,

        #[Required]
        public readonly string $name,

        #[Numeric]
        public readonly int $priceAdjustment = 0,  // In minor units

        #[Numeric, Min(1)]
        public readonly int $maxQuantity = 1,

        public readonly bool $isDefault = false,

        public readonly bool $isActive = true,

        #[Numeric, Min(0)]
        public readonly int $sortOrder = 0,

        public readonly ?Carbon $createdAt = null,

        public readonly ?Carbon $updatedAt = null,
    ) {}

    /**
     * Calculate total price impact for given quantity
     * @return int Price impact in minor units
     */
    public function calculatePriceImpact(int $quantity = 1): int
    {
        return $this->priceAdjustment * min($quantity, $this->maxQuantity);
    }

    /**
     * Get display name with price
     * Note: Use formatCurrency() from the frontend for proper formatting
     */
    public function getDisplayName(): string
    {
        if ($this->priceAdjustment === 0) {
            return $this->name;
        }

        $sign = $this->priceAdjustment > 0 ? '+' : '';
        // Just return the name with a sign indicator, formatting should be done in frontend
        return sprintf('%s (%s)', $this->name, $sign);
    }
}
