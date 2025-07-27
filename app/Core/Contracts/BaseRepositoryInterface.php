<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Base repository interface for all module repositories
 */
interface BaseRepositoryInterface
{
    /**
     * Find entity by ID
     */
    public function find(int $id): ?object;

    /**
     * Find entity by ID or throw exception
     */
    public function findOrFail(int $id): object;

    /**
     * Get all entities
     */
    public function all(): array;

    /**
     * Get paginated entities
     * 
     * @param int $perPage Number of items per page
     * @param array $columns Columns to select
     * @param string $pageName Page parameter name
     * @param int|null $page Current page number
     * @return LengthAwarePaginator
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator;

    /**
     * Create new entity
     */
    public function create(array $data): object;

    /**
     * Update existing entity
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete entity
     */
    public function delete(int $id): bool;

    /**
     * Check if entity exists
     */
    public function exists(int $id): bool;
}