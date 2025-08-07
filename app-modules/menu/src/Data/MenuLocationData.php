<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\MenuLocation;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\IntegerType;

class MenuLocationData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required, IntegerType]
        public readonly int $menuId,
        #[Required, IntegerType]
        public readonly int $locationId,
        public readonly bool $isActive = true,
        public readonly bool $isPrimary = false,
        public readonly ?\DateTimeInterface $activatedAt = null,
        public readonly ?\DateTimeInterface $deactivatedAt = null,
        public readonly ?array $overrides = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}
    
    public static function fromModel(MenuLocation $location): self
    {
        return new self(
            id: $location->id,
            menuId: $location->menu_id,
            locationId: $location->location_id,
            isActive: $location->is_active,
            isPrimary: $location->is_primary,
            activatedAt: $location->activated_at,
            deactivatedAt: $location->deactivated_at,
            overrides: $location->overrides,
            createdAt: $location->created_at,
            updatedAt: $location->updated_at,
        );
    }
}