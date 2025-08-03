<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RecipeIngredientData extends BaseData
{
    public function __construct(
        public readonly ?int $id,

        #[Required, Numeric]
        public readonly int $recipeId,

        #[Required, Numeric]
        public readonly int $ingredientId,

        #[Required, Numeric, Min(0.001)]
        public readonly float $quantity,

        #[Required]
        public readonly string $unit,

        public readonly bool $isOptional = false,

        // Include the ingredient data when needed
        public readonly ?IngredientData $ingredient = null,
    ) {}

    /**
     * Calculate the cost of this ingredient in the recipe
     */
    public function calculateCost(): float
    {
        if (!$this->ingredient) {
            return 0;
        }

        // Convert quantity to base unit if needed
        $quantityInBaseUnit = $this->convertToBaseUnit();

        return $quantityInBaseUnit * $this->ingredient->costPerUnit;
    }

    /**
     * Convert quantity to ingredient's base unit
     */
    private function convertToBaseUnit(): float
    {
        // If units match, no conversion needed
        if ($this->unit === $this->ingredient?->unit) {
            return $this->quantity;
        }

        // Simple conversion logic (can be expanded)
        $conversions = [
            'kg' => ['g' => 1000],
            'l' => ['ml' => 1000],
            'dozen' => ['unit' => 12],
            'case' => ['unit' => 24], // Default case size
        ];

        $fromUnit = strtolower($this->unit);
        $toUnit = strtolower($this->ingredient?->unit ?? '');

        if (isset($conversions[$fromUnit][$toUnit])) {
            return $this->quantity * $conversions[$fromUnit][$toUnit];
        }

        if (isset($conversions[$toUnit][$fromUnit])) {
            return $this->quantity / $conversions[$toUnit][$fromUnit];
        }

        // If no conversion found, return as is
        return $this->quantity;
    }

    /**
     * Get formatted quantity with unit
     */
    public function getFormattedQuantity(): string
    {
        return sprintf('%.2f %s', $this->quantity, $this->unit);
    }
}
