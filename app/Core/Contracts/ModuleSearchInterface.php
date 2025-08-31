<?php

namespace App\Core\Contracts;

use App\Core\Data\SearchResultData;

interface ModuleSearchInterface
{
    /**
     * Search within the module's domain.
     */
    public function search(string $query, array $filters = []): SearchResultData;
    
    /**
     * Get searchable fields configuration.
     */
    public function getSearchableFields(): array;
    
    /**
     * Get filter configuration for the module.
     */
    public function getFilterableFields(): array;
    
    /**
     * Get sort configuration for the module.
     */
    public function getSortableFields(): array;
    
    /**
     * Record a search selection for learning.
     */
    public function recordSelection(string $searchId, mixed $entityId): void;
}