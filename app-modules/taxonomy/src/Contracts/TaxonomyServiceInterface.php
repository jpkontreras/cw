<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Contracts;

use Colame\Taxonomy\Data\CreateTaxonomyData;
use Colame\Taxonomy\Data\TaxonomyData;
use Colame\Taxonomy\Data\UpdateTaxonomyData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\DataCollection;

interface TaxonomyServiceInterface
{
    /**
     * Create a new taxonomy
     */
    public function createTaxonomy(CreateTaxonomyData $data): TaxonomyData;
    
    /**
     * Update an existing taxonomy
     */
    public function updateTaxonomy(int $id, UpdateTaxonomyData $data): TaxonomyData;
    
    /**
     * Delete a taxonomy
     */
    public function deleteTaxonomy(int $id): bool;
    
    /**
     * Get taxonomy by ID
     */
    public function getTaxonomy(int $id): ?TaxonomyData;
    
    /**
     * Get taxonomies by type
     */
    public function getTaxonomiesByType(TaxonomyType $type, ?int $locationId = null): DataCollection;
    
    /**
     * Get hierarchical taxonomy tree
     */
    public function getTaxonomyTree(TaxonomyType $type, ?int $locationId = null): array;
    
    /**
     * Assign taxonomies to an entity
     */
    public function assignTaxonomies(Model $entity, array $taxonomyIds, ?TaxonomyType $type = null): void;
    
    /**
     * Get entity taxonomies
     */
    public function getEntityTaxonomies(Model $entity, ?TaxonomyType $type = null): DataCollection;
    
    /**
     * Search taxonomies
     */
    public function searchTaxonomies(string $query, ?TaxonomyType $type = null): DataCollection;
    
    /**
     * Validate taxonomy compatibility
     */
    public function validateCompatibility(array $taxonomyIds): bool;
    
    /**
     * Get popular taxonomies
     */
    public function getPopularTaxonomies(TaxonomyType $type, int $limit = 10): DataCollection;
    
    /**
     * Merge taxonomies
     */
    public function mergeTaxonomies(int $sourceId, int $targetId): bool;
    
    /**
     * Bulk create taxonomies
     */
    public function bulkCreateTaxonomies(array $taxonomies): DataCollection;
}