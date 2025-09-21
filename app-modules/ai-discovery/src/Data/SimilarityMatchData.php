<?php

namespace Colame\AiDiscovery\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\DataCollection;

class SimilarityMatchData extends BaseData
{
    public function __construct(
        #[Required] public readonly string $itemName,
        #[Required] public readonly string $matchedItemName,
        #[Required] public readonly float $similarityScore,
        public readonly ?string $itemCategory,
        public readonly DataCollection $suggestedVariants,
        public readonly DataCollection $suggestedModifiers,
        public readonly ?array $metadata = null,
        public readonly ?array $pricingIntelligence = null,
        public readonly int $usageCount = 0,
        public readonly ?string $lastUsedAt = null,
    ) {}

    #[Computed]
    public function isExactMatch(): bool
    {
        return $this->similarityScore === 100.0;
    }

    #[Computed]
    public function isHighMatch(): bool
    {
        return $this->similarityScore >= 90.0;
    }

    #[Computed]
    public function isMediumMatch(): bool
    {
        return $this->similarityScore >= 80.0 && $this->similarityScore < 90.0;
    }

    #[Computed]
    public function matchLevel(): string
    {
        if ($this->isExactMatch()) return 'exact';
        if ($this->isHighMatch()) return 'high';
        if ($this->isMediumMatch()) return 'medium';
        return 'low';
    }

    #[Computed]
    public function confidenceLevel(): string
    {
        if ($this->similarityScore >= 95) return 'very_high';
        if ($this->similarityScore >= 90) return 'high';
        if ($this->similarityScore >= 80) return 'medium';
        return 'low';
    }
}