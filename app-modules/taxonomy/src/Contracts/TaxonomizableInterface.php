<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Contracts;

use Colame\Taxonomy\Enums\TaxonomyType;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface TaxonomizableInterface
{
    /**
     * Get the taxonomies relationship
     */
    public function taxonomies(): MorphToMany;
    
    /**
     * Sync taxonomies for the model
     */
    public function syncTaxonomies(array $taxonomyIds): void;
    
    /**
     * Check if model has a specific taxonomy
     */
    public function hasTaxonomy(int|string $taxonomyIdOrSlug): bool;
    
    /**
     * Get taxonomies of a specific type
     */
    public function getTaxonomiesByType(TaxonomyType $type): \Illuminate\Support\Collection;
    
    /**
     * Attach a taxonomy with metadata
     */
    public function attachTaxonomy(int $taxonomyId, array $metadata = []): void;
    
    /**
     * Detach a taxonomy
     */
    public function detachTaxonomy(int $taxonomyId): void;
}