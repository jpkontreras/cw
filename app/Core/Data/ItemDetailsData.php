<?php

declare(strict_types=1);

namespace App\Core\Data;

use Spatie\LaravelData\Attributes\Computed;

/**
 * Data transfer object for item details used in cross-module communication
 */
class ItemDetailsData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $basePrice,
        public readonly ?string $sku,
        public readonly ?string $category,
        public readonly ?int $preparationTime,
        public readonly ?string $imageUrl,
        public readonly bool $isActive = true,
        public readonly ?array $metadata = null,
    ) {}
    
    #[Computed]
    public function formattedPrice(): string
    {
        return '$' . number_format($this->basePrice, 2);
    }
    
    #[Computed]
    public function hasImage(): bool
    {
        return !empty($this->imageUrl);
    }
}