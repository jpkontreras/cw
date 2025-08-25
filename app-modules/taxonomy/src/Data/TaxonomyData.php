<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use App\Core\Data\BaseData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Colame\Taxonomy\Models\Taxonomy;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

class TaxonomyData extends BaseData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        #[WithCast(EnumCast::class)]
        public TaxonomyType $type,
        public ?int $parentId,
        public ?int $locationId,
        public ?TaxonomyMetadataData $metadata,
        public int $sortOrder = 0,
        public bool $isActive = true,
        #[DataCollectionOf(TaxonomyData::class)]
        public Lazy|DataCollection|null $children = null,
        public Lazy|TaxonomyData|null $parent = null,
        #[DataCollectionOf(TaxonomyAttributeData::class)]
        public Lazy|DataCollection|null $attributes = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
    
    #[Computed]
    public function fullPath(): string
    {
        if ($this->parent instanceof TaxonomyData) {
            return $this->parent->fullPath() . ' > ' . $this->name;
        }
        return $this->name;
    }
    
    #[Computed]
    public function isHierarchical(): bool
    {
        return $this->type->isHierarchical();
    }
    
    #[Computed]
    public function allowsMultiple(): bool
    {
        return $this->type->allowsMultiple();
    }
    
    public static function fromModel(Taxonomy $taxonomy): self
    {
        return new self(
            id: $taxonomy->id,
            name: $taxonomy->name,
            slug: $taxonomy->slug,
            type: TaxonomyType::from($taxonomy->type),
            parentId: $taxonomy->parent_id,
            locationId: $taxonomy->location_id,
            metadata: $taxonomy->metadata ? TaxonomyMetadataData::from($taxonomy->metadata) : null,
            sortOrder: $taxonomy->sort_order,
            isActive: $taxonomy->is_active,
            children: Lazy::whenLoaded('children', $taxonomy, 
                fn() => TaxonomyData::collection($taxonomy->children)
            ),
            parent: Lazy::whenLoaded('parent', $taxonomy,
                fn() => $taxonomy->parent ? TaxonomyData::from($taxonomy->parent) : null
            ),
            attributes: Lazy::whenLoaded('attributes', $taxonomy,
                fn() => TaxonomyAttributeData::collection($taxonomy->attributes)
            ),
            createdAt: $taxonomy->created_at?->toISOString(),
            updatedAt: $taxonomy->updated_at?->toISOString(),
        );
    }
}