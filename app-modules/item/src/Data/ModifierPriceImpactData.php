<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ModifierPriceImpactData extends BaseData
{
    public function __construct(
        public readonly int $modifierId,

        public readonly string $modifierName,

        public readonly int $modifierGroupId,

        public readonly string $modifierGroupName,

        public readonly int $quantity,

        public readonly float $unitPrice,

        public readonly float $priceImpact,
    ) {}

    /**
     * Get display string for the modifier
     */
    public function getDisplayString(): string
    {
        if ($this->quantity > 1) {
            return sprintf('%s x%d', $this->modifierName, $this->quantity);
        }

        return $this->modifierName;
    }

    /**
     * Check if this is a charge (positive price impact)
     */
    public function isCharge(): bool
    {
        return $this->priceImpact > 0;
    }

    /**
     * Check if this is a discount (negative price impact)
     */
    public function isDiscount(): bool
    {
        return $this->priceImpact < 0;
    }
}
