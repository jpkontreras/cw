<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class MenuStructureData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $type,
        public readonly bool $isActive,
        public readonly bool $isAvailable,
        
        #[DataCollectionOf(MenuSectionWithItemsData::class)]
        public readonly DataCollection $sections,
        
        public readonly ?MenuAvailabilityData $availability,
        public readonly ?array $metadata,
    ) {}
}