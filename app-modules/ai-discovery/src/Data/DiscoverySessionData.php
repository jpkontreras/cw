<?php

namespace Colame\AiDiscovery\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Lazy;

class DiscoverySessionData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required] public readonly int $userId,
        public readonly ?int $itemId,
        #[Required] public readonly string $sessionUuid,
        public readonly RestaurantContextData $restaurantContext,
        public readonly array $conversationHistory,
        public readonly ExtractedDataData $extractedData,
        public readonly array $confidenceScores,
        #[Required] public readonly string $status,
        public readonly int $messagesCount = 0,
        public readonly int $tokensUsed = 0,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    #[Computed]
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    #[Computed]
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    #[Computed]
    public function averageConfidence(): float
    {
        if (empty($this->confidenceScores)) {
            return 0.0;
        }

        return round(array_sum($this->confidenceScores) / count($this->confidenceScores), 2);
    }
}