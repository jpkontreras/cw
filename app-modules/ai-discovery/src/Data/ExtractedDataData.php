<?php

namespace Colame\AiDiscovery\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;

class ExtractedDataData extends BaseData
{
    public function __construct(
        public readonly array $variants = [],
        public readonly array $modifiers = [],
        public readonly array $metadata = [],
        public readonly ?array $pricingIntelligence = null,
        public readonly ?array $allergens = null,
        public readonly ?array $nutritionalInfo = null,
        public readonly ?array $culturalContext = null,
    ) {}

    #[Computed]
    public function hasVariants(): bool
    {
        return !empty($this->variants);
    }

    #[Computed]
    public function hasModifiers(): bool
    {
        return !empty($this->modifiers);
    }

    #[Computed]
    public function completenessScore(): float
    {
        $score = 0;
        $totalFields = 7;

        if (!empty($this->variants)) $score++;
        if (!empty($this->modifiers)) $score++;
        if (!empty($this->metadata)) $score++;
        if (!empty($this->pricingIntelligence)) $score++;
        if (!empty($this->allergens)) $score++;
        if (!empty($this->nutritionalInfo)) $score++;
        if (!empty($this->culturalContext)) $score++;

        return round(($score / $totalFields) * 100, 2);
    }
}