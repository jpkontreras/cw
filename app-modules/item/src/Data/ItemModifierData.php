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
        public readonly float $priceAdjustment = 0,

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
     */
    public function calculatePriceImpact(int $quantity = 1): float
    {
        return $this->priceAdjustment * min($quantity, $this->maxQuantity);
    }

    /**
     * Get display name with price
     */
    public function getDisplayName(string $currency = 'CLP'): string
    {
        if ($this->priceAdjustment === 0.0) {
            return $this->name;
        }

        $sign = $this->priceAdjustment > 0 ? '+' : '';
        return sprintf('%s (%s%s %s)', $this->name, $sign, number_format($this->priceAdjustment, 0), $currency);
    }
}
