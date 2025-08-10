<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class MenuItemModifiersConfigData extends BaseData
{
    public function __construct(
        #[DataCollectionOf(MenuItemModifierOptionData::class)]
        public DataCollection $options,
        public ?int $minSelections = null,
        public ?int $maxSelections = null,
        public bool $isRequired = false,
    ) {}
}

class MenuItemModifierOptionData extends BaseData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?float $priceAdjustment = null,
        public bool $isDefault = false,
        public ?int $maxQuantity = null,
        public ?string $group = null,
    ) {}
}