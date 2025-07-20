<?php

declare(strict_types=1);

namespace Colame\Order\Services;

use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Services\BaseService;
use Colame\Order\Contracts\OrderItemRepositoryInterface;
use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\OrderItemData;
use Colame\Order\Data\OrderWithRelationsData;
use Colame\Order\Data\UpdateOrderData;
use Colame\Order\Events\OrderCreated;
use Colame\Order\Events\OrderStatusChanged;
use Colame\Order\Exceptions\InvalidOrderStateException;
use Colame\Order\Exceptions\OrderNotFoundException;
use Colame\Order\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Order service implementation
 */
class OrderService extends BaseService implements OrderServiceInterface
{
    public function __construct(
        FeatureFlagInterface $features,
        private OrderRepositoryInterface $orderRepository,
        private OrderItemRepositoryInterface $itemRepository,
        private OrderCalculationService $calculationService,
        private OrderValidationService $validationService,
    ) {
        parent::__construct($features);
    }

    /**
     * Create a new order
     */
    public function createOrder(CreateOrderData $data): OrderData
    {
        $this->logAction('Creating order', ['userId' => $data->userId, 'locationId' => $data->locationId]);

        return DB::transaction(function () use ($data) {
            // Validate order items
            $this->validationService->validateOrderItems($data->items->toArray());

            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Create order
            $orderData = [
                'order_number' => $orderNumber,
                'user_id' => $data->userId,
                'location_id' => $data->locationId,
                'status' => Order::STATUS_DRAFT,
                'type' => $data->type,
                'priority' => 'normal',
                'table_number' => $data->tableNumber,
                'customer_name' => $data->customerName,
                'customer_phone' => $data->customerPhone,
                'customer_email' => $data->customerEmail,
                'delivery_address' => $data->deliveryAddress,
                'notes' => $data->notes,
                'special_instructions' => $data->specialInstructions,
                'payment_status' => Order::PAYMENT_PENDING,
                'metadata' => $data->metadata,
                'subtotal' => 0,
                'tax_amount' => 0,
                'tip_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
            ];

            $order = $this->orderRepository->create($orderData);

            // Create order items
            foreach ($data->items as $itemData) {
                // In a real implementation, we would fetch item details from item service
                // For now, using placeholder values
                $unitPrice = $itemData->unit_price ?: 10.00; // Default price
                
                $this->itemRepository->create([
                    'order_id' => $order->id,
                    'item_id' => $itemData->item_id,
                    'item_name' => $this->getItemName($itemData->item_id), // Would come from item service
                    'quantity' => $itemData->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $itemData->quantity * $unitPrice,
                    'notes' => $itemData->notes,
                    'modifiers' => $itemData->modifiers,
                    'metadata' => $itemData->metadata,
                    'status' => 'pending',
                    'kitchen_status' => 'pending',
                ]);
            }

            // Calculate totals
            $totals = $this->calculationService->calculateOrderTotals($order->id);
            $this->orderRepository->update($order->id, $totals);

            // Apply offers if provided
            if ($data->offerCodes) {
                $order = $this->applyOffers($order->id, $data->offerCodes);
            }

            // Reload order with items
            $order = $this->orderRepository->find($order->id);

            // Fire event
            Event::dispatch(new OrderCreated($order));

            return $order;
        });
    }

    /**
     * Update an existing order
     */
    public function updateOrder(int $id, UpdateOrderData $data): OrderData
    {
        $order = $this->orderRepository->find($id);
        
        if (!$order) {
            throw new OrderNotFoundException("Order {$id} not found");
        }

        if (!$order->canBeModified()) {
            throw new InvalidOrderStateException("Order cannot be modified in status: {$order->status}");
        }

        $this->logAction('Updating order', ['orderId' => $id]);

        return DB::transaction(function () use ($id, $data) {
            $updateData = [];

            if (!($data->notes instanceof \Spatie\LaravelData\Optional)) {
                $updateData['notes'] = $data->notes;
            }

            if (!($data->customerName instanceof \Spatie\LaravelData\Optional)) {
                $updateData['customer_name'] = $data->customerName;
            }

            if (!($data->customerPhone instanceof \Spatie\LaravelData\Optional)) {
                $updateData['customer_phone'] = $data->customerPhone;
            }

            if (!($data->metadata instanceof \Spatie\LaravelData\Optional)) {
                $updateData['metadata'] = $data->metadata;
            }

            // Update order details
            if (!empty($updateData)) {
                $this->orderRepository->update($id, $updateData);
            }

            // Update items if provided
            if ($data->hasItemsUpdate()) {
                $this->updateOrderItems($id, $data->items);
                
                // Recalculate totals
                $totals = $this->calculationService->calculateOrderTotals($id);
                $this->orderRepository->update($id, $totals);
            }

            return $this->orderRepository->find($id);
        });
    }

    /**
     * Get order with all relations
     */
    public function getOrderWithRelations(int $id): ?OrderWithRelationsData
    {
        $order = $this->orderRepository->find($id);
        
        if (!$order) {
            return null;
        }

        // In a real implementation, these would come from other services
        // through their interfaces
        $user = null; // $this->userService->find($order->userId);
        $location = null; // $this->locationService->find($order->locationId);
        $payments = []; // $this->paymentService->getByOrder($id);
        $offers = []; // $this->offerService->getByOrder($id);

        return new OrderWithRelationsData(
            order: $order,
            user: $user,
            location: $location,
            payments: $payments,
            offers: $offers,
        );
    }

    /**
     * Confirm an order
     */
    public function confirmOrder(int $id): OrderData
    {
        return $this->updateOrderStatus($id, Order::STATUS_CONFIRMED, 'placed');
    }

    /**
     * Mark order as preparing
     */
    public function startPreparingOrder(int $id): OrderData
    {
        return $this->updateOrderStatus($id, Order::STATUS_PREPARING, 'confirmed');
    }

    /**
     * Mark order as ready
     */
    public function markOrderReady(int $id): OrderData
    {
        return $this->updateOrderStatus($id, Order::STATUS_READY, 'preparing');
    }

    /**
     * Complete an order
     */
    public function completeOrder(int $id): OrderData
    {
        return $this->updateOrderStatus($id, Order::STATUS_COMPLETED, 'ready');
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(int $id, string $reason): OrderData
    {
        $order = $this->orderRepository->find($id);
        
        if (!$order) {
            throw new OrderNotFoundException("Order {$id} not found");
        }

        if (!$order->canBeCancelled()) {
            throw new InvalidOrderStateException("Order cannot be cancelled in status: {$order->status}");
        }

        $this->logAction('Cancelling order', ['orderId' => $id, 'reason' => $reason]);

        $this->orderRepository->updateStatus($id, Order::STATUS_CANCELLED, $reason);
        
        $order = $this->orderRepository->find($id);
        
        Event::dispatch(new OrderStatusChanged($order, $order->status, Order::STATUS_CANCELLED));

        return $order;
    }

    /**
     * Calculate order totals
     */
    public function calculateOrderTotals(int $id): array
    {
        return $this->calculationService->calculateOrderTotals($id);
    }

    /**
     * Validate order items availability
     */
    public function validateOrderItems(array $items): bool
    {
        return $this->validationService->validateOrderItems($items);
    }

    /**
     * Apply offers to order
     */
    public function applyOffers(int $orderId, array $offerCodes): OrderData
    {
        if (!$this->isFeatureEnabled('order.offers')) {
            throw new \RuntimeException('Order offers feature is not enabled');
        }

        $this->logAction('Applying offers', ['orderId' => $orderId, 'offers' => $offerCodes]);

        // In real implementation, this would interact with offer service
        // For now, just return the order
        return $this->orderRepository->find($orderId);
    }

    /**
     * Get active orders for kitchen
     */
    public function getKitchenOrders(int $locationId): array
    {
        if (!$this->isFeatureEnabled('order.kitchen_display')) {
            return [];
        }

        return $this->orderRepository->getActiveKitchenOrders($locationId);
    }

    /**
     * Update order item status
     */
    public function updateOrderItemStatus(int $orderId, int $itemId, string $status): bool
    {
        $this->logAction('Updating order item status', [
            'orderId' => $orderId,
            'itemId' => $itemId,
            'status' => $status
        ]);

        return $this->itemRepository->updateStatus($itemId, $status);
    }

    /**
     * Split order into multiple orders
     */
    public function splitOrder(int $orderId, array $itemGroups): array
    {
        if (!$this->isFeatureEnabled('order.split_bill')) {
            throw new \RuntimeException('Split bill feature is not enabled');
        }

        $this->logAction('Splitting order', ['orderId' => $orderId, 'groups' => count($itemGroups)]);

        // Implementation would split items into new orders
        // For now, return empty array
        return [];
    }

    /**
     * Merge multiple orders
     */
    public function mergeOrders(array $orderIds): OrderData
    {
        $this->logAction('Merging orders', ['orderIds' => $orderIds]);

        // Implementation would merge orders
        // For now, throw exception
        throw new \RuntimeException('Order merging not implemented yet');
    }

    /**
     * Update order status with validation
     */
    private function updateOrderStatus(int $id, string $newStatus, string $expectedCurrentStatus): OrderData
    {
        $order = $this->orderRepository->find($id);
        
        if (!$order) {
            throw new OrderNotFoundException("Order {$id} not found");
        }

        if ($order->status !== $expectedCurrentStatus && $expectedCurrentStatus !== null) {
            throw new InvalidOrderStateException(
                "Order must be in status '{$expectedCurrentStatus}' to transition to '{$newStatus}'"
            );
        }

        $this->logAction('Updating order status', [
            'orderId' => $id,
            'from' => $order->status,
            'to' => $newStatus
        ]);

        $this->orderRepository->updateStatus($id, $newStatus);
        
        $updatedOrder = $this->orderRepository->find($id);
        
        Event::dispatch(new OrderStatusChanged($updatedOrder, $order->status, $newStatus));

        return $updatedOrder;
    }

    /**
     * Update order items
     */
    private function updateOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            if (isset($item['id']) && isset($item['quantity'])) {
                if ($item['quantity'] > 0) {
                    $this->itemRepository->update($item['id'], ['quantity' => $item['quantity']]);
                } else {
                    $this->itemRepository->delete($item['id']);
                }
            }
        }
    }

    /**
     * Get item name (placeholder - would come from item service)
     */
    private function getItemName(int $itemId): string
    {
        // In real implementation, this would call item service
        return "Item #{$itemId}";
    }

    /**
     * Get paginated orders with filters
     */
    public function getPaginatedOrders(array $filters, int $perPage = 20): array
    {
        $query = Order::query()->with('items');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_phone', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Date filters
        if (!empty($filters['date'])) {
            switch ($filters['date']) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', today()->subDay());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month);
                    break;
            }
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        // Get paginated results
        $paginated = $query->paginate($perPage);

        // Transform to DTOs
        $orders = collect($paginated->items())->map(function ($order) {
            return OrderData::from($order);
        });

        return [
            'data' => $orders,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ];
    }

    /**
     * Get order statistics
     */
    public function getOrderStats(array $filters = []): array
    {
        $query = Order::query();

        // Apply same filters as listing
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        // Date filter for stats (default to today)
        $dateQuery = clone $query;
        if (!empty($filters['date'])) {
            switch ($filters['date']) {
                case 'today':
                    $dateQuery->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $dateQuery->whereDate('created_at', today()->subDay());
                    break;
                case 'week':
                    $dateQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $dateQuery->whereMonth('created_at', now()->month);
                    break;
            }
        } else {
            $dateQuery->whereDate('created_at', today());
        }

        // Calculate stats
        $totalOrders = $dateQuery->count();
        $activeOrders = clone $query;
        $activeOrders = $activeOrders->whereNotIn('status', ['completed', 'cancelled', 'refunded'])->count();
        
        $readyToServe = clone $query;
        $readyToServe = $readyToServe->where('status', 'ready')->count();
        
        $revenueToday = clone $dateQuery;
        $revenueToday = $revenueToday->whereIn('status', ['completed', 'delivered'])
                                     ->sum('total_amount');

        $avgOrderValue = $totalOrders > 0 ? $revenueToday / $totalOrders : 0;
        
        $completedOrders = clone $dateQuery;
        $completedOrders = $completedOrders->whereIn('status', ['completed', 'delivered'])->count();
        $completionRate = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;

        return [
            'total_orders' => $totalOrders,
            'active_orders' => $activeOrders,
            'ready_to_serve' => $readyToServe,
            'revenue_today' => $revenueToday,
            'average_order_value' => round($avgOrderValue, 2),
            'completion_rate' => round($completionRate, 1),
        ];
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData(array $filters = []): array
    {
        $period = $filters['period'] ?? 'today';
        $locationId = $filters['location_id'] ?? null;

        // Get metrics
        $metrics = $this->getDashboardMetrics($period, $locationId);
        
        // Get hourly distribution
        $hourlyOrders = $this->getHourlyOrderDistribution($period, $locationId);
        
        // Get order type distribution
        $ordersByType = $this->getOrderTypeDistribution($period, $locationId);
        
        // Get order status distribution
        $ordersByStatus = $this->getOrderStatusDistribution($locationId);
        
        // Get top items
        $topItems = $this->getTopSellingItems($period, $locationId);
        
        // Get location performance (if multiple locations)
        $locationPerformance = $this->getLocationPerformance($period);
        
        // Get staff performance
        $staffPerformance = $this->getStaffPerformance($period, $locationId);
        
        // Get recent orders
        $recentOrders = Order::query()
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($order) => OrderData::from($order));

        return [
            'metrics' => $metrics,
            'hourlyOrders' => $hourlyOrders,
            'ordersByType' => $ordersByType,
            'ordersByStatus' => $ordersByStatus,
            'topItems' => $topItems,
            'locationPerformance' => $locationPerformance,
            'staffPerformance' => $staffPerformance,
            'recentOrders' => $recentOrders,
            'filters' => $filters,
        ];
    }

    private function getDashboardMetrics(string $period, ?int $locationId): array
    {
        $query = Order::query()
            ->when($locationId, fn($q) => $q->where('location_id', $locationId));
        
        $dateQuery = clone $query;
        $this->applyDateFilter($dateQuery, $period);
        
        $totalOrders = $dateQuery->count();
        $completedOrders = clone $dateQuery;
        $completedOrders = $completedOrders->whereIn('status', ['completed', 'delivered'])->count();
        
        $revenue = clone $dateQuery;
        $revenue = $revenue->whereIn('status', ['completed', 'delivered'])->sum('total_amount');
        
        $activeOrders = clone $query;
        $activeOrders = $activeOrders->whereNotIn('status', ['completed', 'cancelled', 'refunded'])->count();
        
        $pendingOrders = clone $query;
        $pendingOrders = $pendingOrders->whereIn('status', ['placed', 'confirmed'])->count();
        
        // Calculate average preparation time (mock data for now)
        $avgPrepTime = 25; // minutes
        
        return [
            'totalRevenue' => $revenue,
            'totalOrders' => $totalOrders,
            'averageOrderValue' => $totalOrders > 0 ? $revenue / $totalOrders : 0,
            'avgPreparationTime' => $avgPrepTime,
            'completionRate' => $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0,
            'satisfactionRate' => 85, // Mock data
            'activeOrders' => $activeOrders,
            'pendingOrders' => $pendingOrders,
        ];
    }

    private function getHourlyOrderDistribution(string $period, ?int $locationId): array
    {
        $query = Order::query()
            ->when($locationId, fn($q) => $q->where('location_id', $locationId));
        
        $this->applyDateFilter($query, $period);
        
        $hourlyData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourQuery = clone $query;
            $count = $hourQuery->whereRaw('HOUR(created_at) = ?', [$hour])->count();
            $revenue = clone $query;
            $revenue = $revenue->whereRaw('HOUR(created_at) = ?', [$hour])
                              ->whereIn('status', ['completed', 'delivered'])
                              ->sum('total_amount');
            
            $hourlyData[] = [
                'hour' => $hour,
                'count' => $count,
                'revenue' => $revenue,
            ];
        }
        
        return $hourlyData;
    }

    private function getOrderTypeDistribution(string $period, ?int $locationId): array
    {
        $query = Order::query()
            ->when($locationId, fn($q) => $q->where('location_id', $locationId));
        
        $this->applyDateFilter($query, $period);
        
        return $query->groupBy('type')
            ->selectRaw('type, COUNT(*) as count, SUM(total_amount) as revenue')
            ->get()
            ->toArray();
    }

    private function getOrderStatusDistribution(?int $locationId): array
    {
        $query = Order::query()
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->whereNotIn('status', ['completed', 'cancelled', 'refunded']);
        
        return $query->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->get()
            ->toArray();
    }

    private function getTopSellingItems(string $period, ?int $locationId): array
    {
        // In real implementation, this would join with order_items
        // For now, return mock data
        return [
            ['id' => '1', 'name' => 'Completo Italiano', 'quantity' => 45, 'revenue' => 157500, 'category' => 'Main Courses'],
            ['id' => '2', 'name' => 'Churrasco', 'quantity' => 38, 'revenue' => 209000, 'category' => 'Main Courses'],
            ['id' => '3', 'name' => 'Empanada de Pino', 'quantity' => 72, 'revenue' => 180000, 'category' => 'Starters'],
            ['id' => '4', 'name' => 'Pisco Sour', 'quantity' => 56, 'revenue' => 196000, 'category' => 'Beverages'],
            ['id' => '5', 'name' => 'Pastel de Choclo', 'quantity' => 28, 'revenue' => 126000, 'category' => 'Main Courses'],
        ];
    }

    private function getLocationPerformance(string $period): array
    {
        // Mock data for location performance
        return [
            ['id' => '1', 'name' => 'Main Branch', 'orders' => 156, 'revenue' => 2450000, 'avgTime' => 22, 'rating' => 4.5],
            ['id' => '2', 'name' => 'Downtown Branch', 'orders' => 134, 'revenue' => 1980000, 'avgTime' => 28, 'rating' => 4.3],
        ];
    }

    private function getStaffPerformance(string $period, ?int $locationId): array
    {
        // Mock data for staff performance
        return [
            ['id' => '1', 'name' => 'Juan Pérez', 'role' => 'Waiter', 'orders' => 45, 'revenue' => 675000],
            ['id' => '2', 'name' => 'María González', 'role' => 'Waiter', 'orders' => 38, 'revenue' => 580000],
            ['id' => '3', 'name' => 'Carlos Silva', 'role' => 'Cashier', 'orders' => 52, 'revenue' => 820000],
            ['id' => '4', 'name' => 'Ana Rodríguez', 'role' => 'Waiter', 'orders' => 34, 'revenue' => 510000],
            ['id' => '5', 'name' => 'Diego Morales', 'role' => 'Kitchen', 'orders' => 156, 'revenue' => 2450000],
        ];
    }

    private function applyDateFilter($query, string $period): void
    {
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'yesterday':
                $query->whereDate('created_at', today()->subDay());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month);
                break;
            case 'quarter':
                $query->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()]);
                break;
        }
    }
}