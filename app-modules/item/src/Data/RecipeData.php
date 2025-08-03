<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RecipeData extends BaseData
{
    public function __construct(
        public readonly ?int $id,

        #[Required, Numeric]
        public readonly int $itemId,

        public readonly ?int $itemVariantId,

        #[Required]
        public readonly string $instructions,

        #[Numeric, Min(0)]
        public readonly int $prepTimeMinutes = 0,

        #[Numeric, Min(0)]
        public readonly int $cookTimeMinutes = 0,

        #[Numeric, Min(0.1)]
        public readonly float $yieldQuantity = 1,

        public readonly string $yieldUnit = 'portion',

        public readonly ?string $notes,

        #[DataCollectionOf(RecipeIngredientData::class)]
        public readonly ?array $ingredients = null,

        public readonly ?float $totalCost = null,

        public readonly ?float $costPerUnit = null,

        public readonly ?Carbon $createdAt = null,

        public readonly ?Carbon $updatedAt = null,
    ) {}

    /**
     * Get total time in minutes
     */
    public function getTotalTimeMinutes(): int
    {
        return $this->prepTimeMinutes + $this->cookTimeMinutes;
    }

    /**
     * Get time as readable string
     */
    public function getTimeDisplay(): string
    {
        $total = $this->getTotalTimeMinutes();

        if ($total === 0) {
            return 'No time specified';
        }

        if ($total < 60) {
            return sprintf('%d min', $total);
        }

        $hours = floor($total / 60);
        $minutes = $total % 60;

        if ($minutes === 0) {
            return sprintf('%d hr', $hours);
        }

        return sprintf('%d hr %d min', $hours, $minutes);
    }

    /**
     * Calculate scaling factor for different yield
     */
    public function getScalingFactor(float $desiredYield): float
    {
        if ($this->yieldQuantity <= 0) {
            return 1;
        }

        return $desiredYield / $this->yieldQuantity;
    }
}
