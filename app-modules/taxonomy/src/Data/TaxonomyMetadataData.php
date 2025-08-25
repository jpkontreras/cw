<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use App\Core\Data\BaseData;

class TaxonomyMetadataData extends BaseData
{
    public function __construct(
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $color = null,
        public ?string $image = null,
        public ?bool $featured = false,
        public ?array $translations = [],
        public ?array $customFields = [],
    ) {}
}