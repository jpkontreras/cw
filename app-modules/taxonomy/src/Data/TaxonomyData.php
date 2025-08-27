<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use App\Core\Data\BaseData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Colame\Taxonomy\Models\Taxonomy;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
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
        #[MapInputName('parentId'), MapOutputName('parentId')]
        public ?int $parent_id,
        #[MapInputName('locationId'), MapOutputName('locationId')]
        public ?int $location_id,
        public ?TaxonomyMetadataData $metadata,
        #[MapInputName('sortOrder'), MapOutputName('sortOrder')]
        public ?int $sort_order = 0,
        #[MapInputName('isActive'), MapOutputName('isActive')]
        public ?bool $is_active = true,
        #[DataCollectionOf(TaxonomyData::class)]
        public Lazy|DataCollection|null $children = null,
        public Lazy|TaxonomyData|null $parent = null,
        #[DataCollectionOf(TaxonomyAttributeData::class)]
        public Lazy|DataCollection|null $attributes = null,
        #[MapInputName('childrenCount'), MapOutputName('childrenCount')]
        public ?int $children_count = null,
        #[MapInputName('createdAt'), MapOutputName('createdAt')]
        public ?string $created_at = null,
        #[MapInputName('updatedAt'), MapOutputName('updatedAt')]
        public ?string $updated_at = null,
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
            parent_id: $taxonomy->parent_id,
            location_id: $taxonomy->location_id,
            metadata: $taxonomy->metadata ? TaxonomyMetadataData::from($taxonomy->metadata) : null,
            sort_order: $taxonomy->sort_order ?? 0,
            is_active: $taxonomy->is_active ?? true,
            children: Lazy::whenLoaded('children', $taxonomy, 
                fn() => self::collect($taxonomy->children, DataCollection::class)
            ),
            parent: Lazy::whenLoaded('parent', $taxonomy,
                fn() => $taxonomy->parent ? self::from($taxonomy->parent) : null
            ),
            attributes: Lazy::whenLoaded('attributes', $taxonomy,
                fn() => TaxonomyAttributeData::collect($taxonomy->attributes, DataCollection::class)
            ),
            children_count: $taxonomy->children_count ?? null,
            created_at: $taxonomy->created_at?->toISOString(),
            updated_at: $taxonomy->updated_at?->toISOString(),
        );
    }
}