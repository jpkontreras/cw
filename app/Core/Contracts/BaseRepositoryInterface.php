<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Base repository interface for all module repositories
 * 
 * IMPORTANT: All repositories MUST return Data Transfer Objects (DTOs), never Eloquent models.
 * This ensures proper encapsulation and prevents cross-module dependencies.
 * 
 * @template T of Data
 */
interface BaseRepositoryInterface
{
    /**
     * Find entity by ID
     * 
     * @param int $id
     * @return T|null Returns a DTO or null if not found
     */
    public function find(int $id): ?Data;

    /**
     * Find entity by ID or throw exception
     * 
     * @param int $id
     * @return T Returns a DTO
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Data;

    /**
     * Get all entities as DTOs
     * 
     * @return array<T> Array of DTOs
     */
    public function all(): array;

    /**
     * Get paginated entities
     * 
     * Note: The paginator should contain DTOs, not models.
     * Use PaginatedResourceData::fromPaginator() in services.
     * 
     * @param int $perPage Number of items per page
     * @param array $columns Columns to select
     * @param string $pageName Page parameter name
     * @param int|null $page Current page number
     * @return LengthAwarePaginator<T>
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator;

    /**
     * Create new entity and return as DTO
     * 
     * @param array $data
     * @return T Returns the created entity as a DTO
     */
    public function create(array $data): Data;

    /**
     * Update existing entity
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete entity
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Check if entity exists
     * 
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * Get the sortable fields for this repository
     * 
     * MUST use DTO property names (camelCase), not database column names.
     * The laravel-data mapping will handle the conversion.
     * 
     * @return array<string> Array of sortable field names
     */
    public function getSortableFields(): array;

    /**
     * Transform a model to DTO
     * 
     * This method should be implemented to handle the transformation
     * of Eloquent models to DTOs, including lazy loading of relations.
     * 
     * @param mixed $model The Eloquent model
     * @return T|null The DTO or null if model is null
     */
    public function toData(mixed $model): ?Data;
}