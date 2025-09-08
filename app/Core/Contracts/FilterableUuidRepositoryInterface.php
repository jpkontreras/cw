<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filterable repository interface for UUID-based entities
 * Combines UUID repository with filtering capabilities
 */
interface FilterableUuidRepositoryInterface extends UuidRepositoryInterface
{
    /**
     * Apply filters to the query
     */
    public function applyFilters(Builder $query, array $filters): Builder;

    /**
     * Get paginated records with filters
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Search records
     */
    public function search(string $term, array $filters = []): LengthAwarePaginator;
}