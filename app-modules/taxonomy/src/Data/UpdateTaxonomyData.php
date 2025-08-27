<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\LaravelData\Attributes\Validation\Unique;

class UpdateTaxonomyData extends BaseData
{
    public function __construct(
        #[Sometimes]
        public ?string $name = null,
        
        #[Sometimes, Unique('taxonomies', 'slug', ignore: new RouteParameterReference('taxonomy'))]
        public ?string $slug = null,
        
        #[Sometimes, MapInputName('parentId')]
        public ?int $parent_id = null,
        
        #[Sometimes, MapInputName('locationId')]
        public ?int $location_id = null,
        
        #[Sometimes]
        public ?TaxonomyMetadataData $metadata = null,
        
        #[Sometimes, MapInputName('sortOrder')]
        public ?int $sort_order = null,
        
        #[Sometimes, MapInputName('isActive')]
        public ?bool $is_active = null,
        
        public ?array $attributes = null,
    ) {}
}