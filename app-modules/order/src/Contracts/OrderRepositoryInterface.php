<?php

declare(strict_types=1);

namespace Colame\Order\Contracts;

use Colame\Order\Data\OrderData;
use Spatie\LaravelData\DataCollection;

/**
 * Order repository interface - Exact match to original module
 */
interface OrderRepositoryInterface
{
    /**
     * Find order by ID
     */
    public function find(string $id): ?OrderData;

    /**
     * Find order by ID or throw exception
     */
    public function findOrFail(string $id): OrderData;

    /**
     * Get all orders
     */
    public function all(): DataCollection;

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): DataCollection;

    /**
     * Get orders for a specific location
     */
    public function getByLocation(int $locationId): DataCollection;

    /**
     * Get orders for a specific user
     */
    public function getByUser(int $userId): DataCollection;

    /**
     * Create new order
     */
    public function create(array $data): OrderData;

    /**
     * Update order
     */
    public function update(string $id, array $data): bool;

    /**
     * Update order status
     */
    public function updateStatus(string $id, string $status, ?string $reason = null): bool;

    /**
     * Delete order
     */
    public function delete(string $id): bool;

    /**
     * Check if order exists
     */
    public function exists(string $id): bool;

    /**
     * Get active orders for kitchen display
     */
    public function getActiveKitchenOrders(int $locationId): DataCollection;

    /**
     * Get order statistics for a date range
     */
    public function getStatistics(int $locationId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Paginate orders with filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): mixed;

    /**
     * Get orders for dashboard
     */
    public function getDashboardOrders(int $locationId, array $filters = []): DataCollection;

    /**
     * Get today's orders for a location
     */
    public function getTodaysOrders(int $locationId): DataCollection;
}