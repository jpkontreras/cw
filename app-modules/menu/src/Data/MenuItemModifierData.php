<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Colame\Menu\Models\MenuItemModifier;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Numeric;

class MenuItemModifierData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required, IntegerType]
        public readonly int $menuItemId,
        #[Required, IntegerType]
        public readonly int $modifierGroupId,
        public readonly ?int $modifierId,
        public readonly bool $isRequired = false,
        public readonly bool $isAvailable = true,
        #[Numeric]
        public readonly ?float $priceOverride = null,
        public readonly ?int $minSelections = null,
        public readonly ?int $maxSelections = null,
        public readonly bool $isDefault = false,
        public readonly int $sortOrder = 0,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}
    
    public static function fromModel(MenuItemModifier $modifier): self
    {
        return new self(
            id: $modifier->id,
            menuItemId: $modifier->menu_item_id,
            modifierGroupId: $modifier->modifier_group_id,
            modifierId: $modifier->modifier_id,
            isRequired: $modifier->is_required,
            isAvailable: $modifier->is_available,
            priceOverride: $modifier->price_override,
            minSelections: $modifier->min_selections,
            maxSelections: $modifier->max_selections,
            isDefault: $modifier->is_default,
            sortOrder: $modifier->sort_order,
            metadata: $modifier->metadata,
            createdAt: $modifier->created_at,
            updatedAt: $modifier->updated_at,
        );
    }
}