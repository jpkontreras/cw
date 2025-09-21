<?php

namespace Colame\AiDiscovery\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Required;

class ExtractedModifierData extends BaseData
{
    public function __construct(
        #[Required] public readonly string $groupName,
        #[Required] public readonly string $name,
        public readonly ?string $displayName,
        #[Required] public readonly string $selectionType, // single, multiple
        public readonly float $priceAdjustment = 0.00,
        public readonly ?float $suggestedPrice = null,
        public readonly bool $isRequired = false,
        public readonly int $minSelections = 0,
        public readonly ?int $maxSelections = null,
        public readonly int $displayOrder = 0,
        public readonly float $confidence = 0.0,
        public readonly ?array $metadata = null,
        public readonly ?array $allergens = null,
        public readonly ?array $nutritionalImpact = null,
        public readonly ?string $reasoning = null,
    ) {}

    #[Computed]
    public function isSingleSelection(): bool
    {
        return $this->selectionType === 'single';
    }

    #[Computed]
    public function isMultipleSelection(): bool
    {
        return $this->selectionType === 'multiple';
    }

    #[Computed]
    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.8;
    }

    #[Computed]
    public function hasAllergens(): bool
    {
        return !empty($this->allergens);
    }

    #[Computed]
    public function formattedName(): string
    {
        return $this->displayName ?: ucfirst($this->name);
    }
}