<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;

class NutritionalInfoData extends BaseData
{
    public function __construct(
        public ?float $calories = null,
        public ?float $totalFat = null,
        public ?float $saturatedFat = null,
        public ?float $transFat = null,
        public ?float $cholesterol = null,
        public ?float $sodium = null,
        public ?float $totalCarbohydrates = null,
        public ?float $dietaryFiber = null,
        public ?float $totalSugars = null,
        public ?float $addedSugars = null,
        public ?float $protein = null,
        public ?float $vitaminA = null,
        public ?float $vitaminC = null,
        public ?float $vitaminD = null,
        public ?float $calcium = null,
        public ?float $iron = null,
        public ?float $potassium = null,
        public ?string $servingSize = null,
        public ?int $servingsPerContainer = null,
        public ?array $customNutrients = [],
    ) {}
    
    /**
     * Get daily value percentage for calories (based on 2000 calorie diet)
     */
    #[Computed]
    public function caloriesDailyValue(): ?float
    {
        return $this->calories !== null ? round(($this->calories / 2000) * 100, 1) : null;
    }
    
    /**
     * Get daily value percentage for total fat (based on 65g)
     */
    #[Computed]
    public function totalFatDailyValue(): ?float
    {
        return $this->totalFat !== null ? round(($this->totalFat / 65) * 100, 1) : null;
    }
    
    /**
     * Get daily value percentage for sodium (based on 2300mg)
     */
    #[Computed]
    public function sodiumDailyValue(): ?float
    {
        return $this->sodium !== null ? round(($this->sodium / 2300) * 100, 1) : null;
    }
    
    /**
     * Get daily value percentage for protein (based on 50g)
     */
    #[Computed]
    public function proteinDailyValue(): ?float
    {
        return $this->protein !== null ? round(($this->protein / 50) * 100, 1) : null;
    }
    
    /**
     * Check if this item is high in calories (>400 per serving)
     */
    #[Computed]
    public function isHighCalorie(): bool
    {
        return $this->calories !== null && $this->calories > 400;
    }
    
    /**
     * Check if this item is low in calories (<200 per serving)
     */
    #[Computed]
    public function isLowCalorie(): bool
    {
        return $this->calories !== null && $this->calories < 200;
    }
    
    /**
     * Get formatted nutritional summary
     */
    public function getSummary(): array
    {
        $summary = [];
        
        if ($this->calories !== null) {
            $summary['calories'] = "{$this->calories} cal";
        }
        if ($this->protein !== null) {
            $summary['protein'] = "{$this->protein}g protein";
        }
        if ($this->totalCarbohydrates !== null) {
            $summary['carbs'] = "{$this->totalCarbohydrates}g carbs";
        }
        if ($this->totalFat !== null) {
            $summary['fat'] = "{$this->totalFat}g fat";
        }
        
        return $summary;
    }
}