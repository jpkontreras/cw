<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class IngredientData extends BaseData
{
    public function __construct(
        public readonly ?int $id,

        #[Required]
        public readonly string $name,

        #[Required]
        public readonly string $unit,

        #[Required, Numeric, Min(0)]
        public readonly float $costPerUnit,

        public readonly ?int $supplierId,

        public readonly ?string $storageRequirements,

        public readonly ?int $shelfLifeDays,

        #[Numeric, Min(0)]
        public readonly float $currentStock = 0,

        #[Numeric, Min(0)]
        public readonly float $reorderLevel = 0,

        #[Numeric, Min(0)]
        public readonly float $reorderQuantity = 0,

        public readonly bool $isActive = true,

        public readonly ?Carbon $createdAt = null,

        public readonly ?Carbon $updatedAt = null,

        public readonly ?Carbon $deletedAt = null,
    ) {}

    /**
     * Check if ingredient needs reorder
     */
    public function needsReorder(): bool
    {
        return $this->currentStock <= $this->reorderLevel;
    }

    /**
     * Check if ingredient is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->currentStock <= 0;
    }

    /**
     * Calculate total value of current stock
     */
    public function getStockValue(): float
    {
        return $this->currentStock * $this->costPerUnit;
    }

    /**
     * Get unit display with proper pluralization
     */
    public function getUnitDisplay(float $quantity = 1): string
    {
        $units = [
            'kg' => ['singular' => 'kg', 'plural' => 'kg'],
            'g' => ['singular' => 'g', 'plural' => 'g'],
            'l' => ['singular' => 'liter', 'plural' => 'liters'],
            'ml' => ['singular' => 'ml', 'plural' => 'ml'],
            'unit' => ['singular' => 'unit', 'plural' => 'units'],
            'piece' => ['singular' => 'piece', 'plural' => 'pieces'],
            'box' => ['singular' => 'box', 'plural' => 'boxes'],
            'can' => ['singular' => 'can', 'plural' => 'cans'],
        ];

        $unitConfig = $units[strtolower($this->unit)] ?? ['singular' => $this->unit, 'plural' => $this->unit . 's'];

        return $quantity == 1 ? $unitConfig['singular'] : $unitConfig['plural'];
    }
}
