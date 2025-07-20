<?php

declare(strict_types=1);

namespace Colame\Order\Contracts;

use Colame\Order\Data\OrderItemData;

/**
 * Order item repository interface
 */
interface OrderItemRepositoryInterface
{
    /**
     * Find order item by ID
     */
    public function find(int $id): ?OrderItemData;

    /**
     * Get all items for an order
     */
    public function getByOrder(int $orderId): array;

    /**
     * Create order item
     */
    public function create(array $data): OrderItemData;

    /**
     * Update order item
     */
    public function update(int $id, array $data): bool;

    /**
     * Update order item status
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Delete order item
     */
    public function delete(int $id): bool;

    /**
     * Get items by status for an order
     */
    public function getByOrderAndStatus(int $orderId, string $status): array;

    /**
     * Bulk update items status
     */
    public function bulkUpdateStatus(array $itemIds, string $status): int;
}