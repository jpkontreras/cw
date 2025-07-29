<?php

declare(strict_types=1);

namespace Colame\Order\Repositories;

use Colame\Order\Contracts\OrderItemRepositoryInterface;
use Colame\Order\Data\OrderItemData;
use Colame\Order\Models\OrderItem;

/**
 * Order item repository implementation
 */
class OrderItemRepository implements OrderItemRepositoryInterface
{
    /**
     * Find order item by ID
     */
    public function find(int $id): ?OrderItemData
    {
        $item = OrderItem::find($id);
        
        if (!$item) {
            return null;
        }

        return OrderItemData::from($item);
    }

    /**
     * Get all items for an order
     */
    public function getByOrder(int $orderId): array
    {
        return OrderItem::where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($item) => OrderItemData::from($item))
            ->all();
    }

    /**
     * Create order item
     */
    public function create(array $data): OrderItemData
    {
        $item = OrderItem::create($data);
        return OrderItemData::from($item);
    }

    /**
     * Update order item
     */
    public function update(int $id, array $data): bool
    {
        return OrderItem::where('id', $id)->update($data) > 0;
    }

    /**
     * Update order item status
     */
    public function updateStatus(int $id, string $status): bool
    {
        return OrderItem::where('id', $id)->update(['status' => $status]) > 0;
    }

    /**
     * Delete order item
     */
    public function delete(int $id): bool
    {
        return OrderItem::destroy($id) > 0;
    }

    /**
     * Get items by status for an order
     */
    public function getByOrderAndStatus(int $orderId, string $status): array
    {
        return OrderItem::where('order_id', $orderId)
            ->where('status', $status)
            ->get()
            ->map(fn($item) => OrderItemData::from($item))
            ->all();
    }

    /**
     * Bulk update items status
     */
    public function bulkUpdateStatus(array $itemIds, string $status): int
    {
        return OrderItem::whereIn('id', $itemIds)->update(['status' => $status]);
    }
}