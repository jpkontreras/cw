<?php

declare(strict_types=1);

namespace Colame\Menu\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class SaveMenuStructureData extends BaseData
{
    public function __construct(
        #[DataCollectionOf(SaveMenuSectionData::class)]
        public readonly ?DataCollection $sections = null,
    ) {}
}