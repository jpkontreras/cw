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
     * @deprecated Use EventSourcedOrderService instead
     * @throws \RuntimeException
     */
    public function create(array $data): OrderItemData
    {
        throw new \RuntimeException(
            'Direct order item creation is not allowed. Use EventSourcedOrderService to add items through event sourcing.'
        );
    }

    /**
     * Update order item
     * @deprecated Use EventSourcedOrderService instead
     * @throws \RuntimeException
     */
    public function update(int $id, array $data): bool
    {
        throw new \RuntimeException(
            'Direct order item updates are not allowed. Use EventSourcedOrderService to modify items through event sourcing.'
        );
    }

    /**
     * Update order item status
     * @deprecated Use EventSourcedOrderService instead
     * @throws \RuntimeException
     */
    public function updateStatus(int $id, string $status): bool
    {
        throw new \RuntimeException(
            'Direct status updates are not allowed. Use EventSourcedOrderService to update item status through event sourcing.'
        );
    }

    /**
     * Delete order item
     * @deprecated Items should not be deleted directly
     * @throws \RuntimeException
     */
    public function delete(int $id): bool
    {
        throw new \RuntimeException(
            'Direct item deletion is not allowed. Use EventSourcedOrderService to remove items through event sourcing.'
        );
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