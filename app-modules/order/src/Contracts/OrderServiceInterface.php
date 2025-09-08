<?php

declare(strict_types=1);

namespace Colame\Order\Contracts;

use App\Core\Data\PaginatedResourceData;
use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\OrderWithRelationsData;
use Colame\Order\Data\UpdateOrderData;
use Spatie\LaravelData\DataCollection;

/**
 * Order service interface for business logic operations
 */
interface OrderServiceInterface
{
    /**
     * Create a new order
     */
    public function createOrder(CreateOrderData $data): OrderData;

    /**
     * Update an existing order
     */
    public function updateOrder(string $id, UpdateOrderData $data): OrderData;

    /**
     * Get order with all relations
     */
    public function getOrderWithRelations(string $id): ?OrderWithRelationsData;

    /**
     * Confirm an order
     */
    public function confirmOrder(string $id): OrderData;

    /**
     * Mark order as preparing
     */
    public function startPreparingOrder(string $id): OrderData;

    /**
     * Mark order as ready
     */
    public function markOrderReady(string $id): OrderData;

    /**
     * Complete an order
     */
    public function completeOrder(string $id): OrderData;

    /**
     * Cancel an order
     */
    public function cancelOrder(string $id, string $reason): OrderData;

    /**
     * Calculate order totals
     */
    public function calculateOrderTotals(string $id): array;

    /**
     * Validate order items availability
     */
    public function validateOrderItems(array $items): bool;

    /**
     * Apply offers to order
     */
    public function applyOffers(string $orderId, array $offerCodes): OrderData;

    /**
     * Get active orders for kitchen
     */
    public function getKitchenOrders(int $locationId): DataCollection;

    /**
     * Update order item status
     */
    public function updateOrderItemStatus(string $orderId, int $itemId, string $status): bool;

    /**
     * Split order into multiple orders
     */
    public function splitOrder(string $orderId, array $itemGroups): array;

    /**
     * Merge multiple orders
     */
    public function mergeOrders(array $orderIds): OrderData;

    /**
     * Get paginated orders with filters
     */
    public function getPaginatedOrders(array $filters, int $perPage = 20): PaginatedResourceData;

    /**
     * Get order statistics
     */
    public function getOrderStats(array $filters = []): array;

    /**
     * Transition order status
     */
    public function transitionOrderStatus(string $id, string $newStatus, ?string $reason = null): OrderData;

    /**
     * Get dashboard data
     */
    public function getDashboardData(array $filters = []): array;
}