<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use App\Core\Data\BaseData;
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
        
        #[Sometimes]
        public ?int $parentId = null,
        
        #[Sometimes]
        public ?int $locationId = null,
        
        #[Sometimes]
        public ?TaxonomyMetadataData $metadata = null,
        
        #[Sometimes]
        public ?int $sortOrder = null,
        
        #[Sometimes]
        public ?bool $isActive = null,
        
        public ?array $attributes = null,
    ) {}
}