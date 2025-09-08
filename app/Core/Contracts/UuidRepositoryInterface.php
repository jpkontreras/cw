<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Base repository interface for UUID-based entities
 */
interface UuidRepositoryInterface
{
    /**
     * Find by UUID
     */
    public function find(string $id): ?Data;

    /**
     * Find by UUID or throw exception
     */
    public function findOrFail(string $id): Data;

    /**
     * Get all records
     */
    public function all(): DataCollection;

    /**
     * Create new record
     */
    public function create(array $data): Data;

    /**
     * Update record by UUID
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete record by UUID
     */
    public function delete(string $id): bool;

    /**
     * Check if record exists by UUID
     */
    public function exists(string $id): bool;
}