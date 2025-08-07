<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\MenuSection;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\DataCollection;

class MenuSectionData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required, IntegerType]
        public readonly int $menuId,
        public readonly ?int $parentId,
        #[Required, StringType]
        public readonly string $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        public readonly ?string $displayName,
        public readonly bool $isActive = true,
        public readonly bool $isFeatured = false,
        public readonly int $sortOrder = 0,
        public readonly ?string $availableFrom = null,
        public readonly ?string $availableUntil = null,
        public readonly ?array $availabilityDays = null,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}
    
    public static function fromModel(MenuSection $section): self
    {
        return new self(
            id: $section->id,
            menuId: $section->menu_id,
            parentId: $section->parent_id,
            name: $section->name,
            slug: $section->slug,
            description: $section->description,
            displayName: $section->display_name,
            isActive: $section->is_active,
            isFeatured: $section->is_featured,
            sortOrder: $section->sort_order,
            availableFrom: $section->available_from?->format('H:i'),
            availableUntil: $section->available_until?->format('H:i'),
            availabilityDays: $section->availability_days,
            metadata: $section->metadata,
            createdAt: $section->created_at,
            updatedAt: $section->updated_at,
        );
    }
}