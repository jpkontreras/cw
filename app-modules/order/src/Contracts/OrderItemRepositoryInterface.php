<?php

declare(strict_types=1);

namespace Colame\Order\Contracts;

use Colame\Order\Data\OrderItemData;
use Spatie\LaravelData\DataCollection;

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
    public function getByOrderId(string $orderId): DataCollection;

    /**
     * Create order item
     */
    public function create(array $data): OrderItemData;

    /**
     * Update order item
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete order item
     */
    public function delete(int $id): bool;

    /**
     * Update item status
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Update kitchen status
     */
    public function updateKitchenStatus(int $id, string $status): bool;

    /**
     * Get items by kitchen status
     */
    public function getByKitchenStatus(string $orderId, string $status): DataCollection;
}