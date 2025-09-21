<?php

namespace Colame\AiDiscovery\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Required;

class ExtractedVariantData extends BaseData
{
    public function __construct(
        #[Required] public readonly string $name,
        public readonly ?string $displayName,
        #[Required] public readonly string $type, // size, flavor, preparation
        public readonly float $priceAdjustment = 0.00,
        public readonly ?float $suggestedPrice = null,
        public readonly int $displayOrder = 0,
        public readonly bool $isDefault = false,
        public readonly float $confidence = 0.0,
        public readonly ?array $metadata = null,
        public readonly ?string $reasoning = null,
    ) {}

    #[Computed]
    public function isSize(): bool
    {
        return $this->type === 'size';
    }

    #[Computed]
    public function isFlavor(): bool
    {
        return $this->type === 'flavor';
    }

    #[Computed]
    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.8;
    }

    #[Computed]
    public function formattedName(): string
    {
        return $this->displayName ?: ucfirst($this->name);
    }
}