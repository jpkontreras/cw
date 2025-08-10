<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

class SaveMenuStructureData extends BaseData
{
    public function __construct(
        #[DataCollectionOf(SaveMenuSectionData::class)]
        public readonly Lazy|DataCollection $sections,
    ) {}
}