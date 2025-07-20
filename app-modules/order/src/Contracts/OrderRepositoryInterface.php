<?php

declare(strict_types=1);

namespace Colame\Order\Contracts;

use Colame\Order\Data\OrderData;
use Colame\Order\Data\OrderWithRelationsData;

/**
 * Order repository interface for domain operations
 */
interface OrderRepositoryInterface
{
    /**
     * Find order by ID
     */
    public function find(int $id): ?OrderData;

    /**
     * Find order by ID or throw exception
     */
    public function findOrFail(int $id): OrderData;

    /**
     * Get all orders
     */
    public function all(): array;

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): array;

    /**
     * Get orders for a specific location
     */
    public function getByLocation(int $locationId): array;

    /**
     * Get orders for a specific user
     */
    public function getByUser(int $userId): array;

    /**
     * Create new order
     */
    public function create(array $data): OrderData;

    /**
     * Update order
     */
    public function update(int $id, array $data): bool;

    /**
     * Update order status
     */
    public function updateStatus(int $id, string $status, ?string $reason = null): bool;

    /**
     * Delete order
     */
    public function delete(int $id): bool;

    /**
     * Check if order exists
     */
    public function exists(int $id): bool;

    /**
     * Get active orders for kitchen display
     */
    public function getActiveKitchenOrders(int $locationId): array;

    /**
     * Get order statistics for a date range
     */
    public function getStatistics(int $locationId, \DateTimeInterface $from, \DateTimeInterface $to): array;
}