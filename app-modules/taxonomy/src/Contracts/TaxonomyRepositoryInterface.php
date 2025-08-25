<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Contracts;

use Colame\Taxonomy\Data\TaxonomyData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\DataCollection;

interface TaxonomyRepositoryInterface
{
    /**
     * Find a taxonomy by ID
     */
    public function find(int $id): ?TaxonomyData;
    
    /**
     * Find a taxonomy by slug
     */
    public function findBySlug(string $slug): ?TaxonomyData;
    
    /**
     * Get all taxonomies of a specific type
     */
    public function findByType(TaxonomyType $type, ?int $locationId = null): DataCollection;
    
    /**
     * Get hierarchical structure of taxonomies
     */
    public function getHierarchy(TaxonomyType $type, ?int $locationId = null): array;
    
    /**
     * Get children of a taxonomy
     */
    public function getChildren(int $parentId): DataCollection;
    
    /**
     * Get ancestors of a taxonomy (breadcrumb trail)
     */
    public function getAncestors(int $taxonomyId): DataCollection;
    
    /**
     * Create a new taxonomy
     */
    public function create(array $data): TaxonomyData;
    
    /**
     * Update a taxonomy
     */
    public function update(int $id, array $data): TaxonomyData;
    
    /**
     * Delete a taxonomy
     */
    public function delete(int $id): bool;
    
    /**
     * Attach a taxonomy to an entity
     */
    public function attachToEntity(int $taxonomyId, Model $entity, array $metadata = []): void;
    
    /**
     * Detach a taxonomy from an entity
     */
    public function detachFromEntity(int $taxonomyId, Model $entity): void;
    
    /**
     * Sync taxonomies for an entity
     */
    public function syncForEntity(Model $entity, array $taxonomyIds, ?TaxonomyType $type = null): void;
    
    /**
     * Get taxonomies for an entity
     */
    public function getForEntity(Model $entity, ?TaxonomyType $type = null): DataCollection;
    
    /**
     * Search taxonomies
     */
    public function search(string $query, ?TaxonomyType $type = null): DataCollection;
}