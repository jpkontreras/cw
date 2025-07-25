<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use App\Core\Data\BaseData;

/**
 * Item modifier data transfer object
 * 
 * Represents individual modifiers that can be applied to items
 */
class ItemModifierData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $groupId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $price,
        public readonly bool $isAvailable,
        public readonly bool $isDefault,
        public readonly int $sortOrder,
        public readonly ?int $maxQuantity,
        public readonly ?array $metadata,
    ) {}

    /**
     * Check if modifier can be added multiple times
     */
    public function allowsMultiple(): bool
    {
        return $this->maxQuantity === null || $this->maxQuantity > 1;
    }
}