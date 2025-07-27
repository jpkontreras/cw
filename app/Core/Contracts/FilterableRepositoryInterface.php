<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interface for repositories that support advanced filtering
 */
interface FilterableRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Apply filters to query
     * 
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function applyFilters(Builder $query, array $filters): Builder;

    /**
     * Get paginated entities with filters
     * 
     * @param array $filters Filter criteria
     * @param int $perPage Number of items per page
     * @param array $columns Columns to select
     * @param string $pageName Page parameter name
     * @param int|null $page Current page number
     * @return LengthAwarePaginator
     */
    public function paginateWithFilters(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator;

    /**
     * Get available filter options for a field
     * 
     * @param string $field Field name
     * @return array
     */
    public function getFilterOptions(string $field): array;

    /**
     * Get searchable fields
     * 
     * @return array<string>
     */
    public function getSearchableFields(): array;

    /**
     * Get sortable fields
     * 
     * @return array<string>
     */
    public function getSortableFields(): array;

    /**
     * Get default sort configuration
     * 
     * @return array{field: string, direction: string}
     */
    public function getDefaultSort(): array;
}