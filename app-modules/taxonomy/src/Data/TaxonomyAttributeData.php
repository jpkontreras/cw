<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Data;

use App\Core\Data\BaseData;
use Colame\Taxonomy\Models\TaxonomyAttribute;

class TaxonomyAttributeData extends BaseData
{
    public function __construct(
        public int $id,
        public int $taxonomyId,
        public string $key,
        public string $value,
        public string $type = 'string', // string, number, boolean, json
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
    
    public static function fromModel(TaxonomyAttribute $attribute): self
    {
        return new self(
            id: $attribute->id,
            taxonomyId: $attribute->taxonomy_id,
            key: $attribute->key,
            value: $attribute->value,
            type: $attribute->type,
            createdAt: $attribute->created_at?->toISOString(),
            updatedAt: $attribute->updated_at?->toISOString(),
        );
    }
    
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'number' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}