<?php

declare(strict_types=1);

namespace Colame\Business\Contracts;

use Colame\Business\Data\BusinessData;
use Colame\Business\Data\CreateBusinessData;
use Colame\Business\Data\UpdateBusinessData;
use Spatie\LaravelData\DataCollection;

interface BusinessRepositoryInterface
{
    /**
     * Find a business by ID
     */
    public function find(int $id): ?BusinessData;

    /**
     * Find a business by slug
     */
    public function findBySlug(string $slug): ?BusinessData;

    /**
     * Get all businesses for a user
     * 
     * @return DataCollection<BusinessData>
     */
    public function getUserBusinesses(int $userId): DataCollection;

    /**
     * Get all businesses
     * 
     * @return DataCollection<BusinessData>
     */
    public function all(): DataCollection;

    /**
     * Get paginated businesses
     */
    public function paginate(array $filters = [], int $perPage = 15): array;

    /**
     * Create a new business
     */
    public function create(CreateBusinessData $data): BusinessData;

    /**
     * Update a business
     */
    public function update(int $id, UpdateBusinessData $data): BusinessData;

    /**
     * Delete a business
     */
    public function delete(int $id): bool;

    /**
     * Check if a slug exists
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool;

    /**
     * Get business with all relations
     */
    public function findWithRelations(int $id): ?BusinessData;

    /**
     * Get businesses by type
     * 
     * @return DataCollection<BusinessData>
     */
    public function getByType(string $type): DataCollection;

    /**
     * Get active businesses
     * 
     * @return DataCollection<BusinessData>
     */
    public function getActive(): DataCollection;

    /**
     * Count businesses for a user
     */
    public function countUserBusinesses(int $userId): int;
}