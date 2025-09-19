<?php

declare(strict_types=1);

namespace Colame\Order\Repositories;

use Colame\Order\Contracts\OrderItemRepositoryInterface;
use Colame\Order\Data\OrderItemData;
use Colame\Order\Models\OrderItem;
use Spatie\LaravelData\DataCollection;

/**
 * Order item repository implementation for event-sourced orders
 * Read-only operations on projected read models
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

        return OrderItemData::fromModel($item);
    }

    /**
     * Get all items for an order
     */
    public function getByOrderId(string $orderId): DataCollection
    {
        return OrderItemData::collection(
            OrderItem::where('order_id', $orderId)
                ->orderBy('created_at', 'asc')
                ->get()
        );
    }

    /**
     * Create order item
     * @throws \RuntimeException Event-sourced items cannot be created directly
     */
    public function create(array $data): OrderItemData
    {
        throw new \RuntimeException(
            'Order items must be created through event sourcing. Use OrderSession aggregate to add items.'
        );
    }

    /**
     * Update order item
     * @throws \RuntimeException Event-sourced items cannot be updated directly
     */
    public function update(int $id, array $data): bool
    {
        throw new \RuntimeException(
            'Order items must be updated through event sourcing. Use OrderSession aggregate to modify items.'
        );
    }

    /**
     * Delete order item
     * @throws \RuntimeException Event-sourced items cannot be deleted directly
     */
    public function delete(int $id): bool
    {
        throw new \RuntimeException(
            'Order items must be removed through event sourcing. Use OrderSession aggregate to remove items.'
        );
    }

    /**
     * Update item status
     * @throws \RuntimeException Event-sourced items cannot have status updated directly
     */
    public function updateStatus(int $id, string $status): bool
    {
        throw new \RuntimeException(
            'Item status must be updated through event sourcing. Use OrderSession aggregate for status updates.'
        );
    }

    /**
     * Update kitchen status
     * @throws \RuntimeException Event-sourced items cannot have kitchen status updated directly
     */
    public function updateKitchenStatus(int $id, string $status): bool
    {
        throw new \RuntimeException(
            'Kitchen status must be updated through event sourcing. Use OrderSession aggregate for kitchen updates.'
        );
    }

    /**
     * Get items by kitchen status
     */
    public function getByKitchenStatus(string $orderId, string $status): DataCollection
    {
        return OrderItemData::collection(
            OrderItem::where('order_id', $orderId)
                ->where('kitchen_status', $status)
                ->get()
        );
    }
}