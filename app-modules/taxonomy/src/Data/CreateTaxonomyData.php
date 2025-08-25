<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use App\Core\Data\BaseData;
use Colame\Taxonomy\Enums\TaxonomyType;
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
        
        public ?int $parentId = null,
        public ?int $locationId = null,
        public ?TaxonomyMetadataData $metadata = null,
        public int $sortOrder = 0,
        public bool $isActive = true,
        public array $attributes = [],
    ) {}
}