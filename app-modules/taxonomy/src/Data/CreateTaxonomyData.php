<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use App\Core\Data\BaseData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;

class CreateTaxonomyData extends BaseData
{
    public function __construct(
        #[Required]
        public string $name,
        
        #[Required, Unique('taxonomies', 'slug')]
        public string $slug,
        
        #[Required, WithCast(EnumCast::class)]
        public TaxonomyType $type,
        
        #[MapInputName('parentId')]
        public ?int $parent_id = null,
        #[MapInputName('locationId')]
        public ?int $location_id = null,
        public ?TaxonomyMetadataData $metadata = null,
        #[MapInputName('sortOrder')]
        public int $sort_order = 0,
        #[MapInputName('isActive')]
        public bool $is_active = true,
        public array $attributes = [],
    ) {}
}