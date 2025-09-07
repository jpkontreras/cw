<?php

declare(strict_types=1);

namespace Colame\Order\Services;

use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Contracts\ResourceMetadataInterface;
use App\Core\Data\ColumnMetadata;
use App\Core\Data\FilterMetadata;
use App\Core\Data\FilterPresetData;
use App\Core\Data\PaginatedResourceData;
use App\Core\Data\ResourceMetadata;
use App\Core\Services\BaseService;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Spatie\LaravelData\DataCollection;
use Colame\Order\Contracts\OrderItemRepositoryInterface;
use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\OrderWithRelationsData;
use Colame\Order\Data\UpdateOrderData;
use Colame\Order\Exceptions\InvalidOrderStateException;
use Colame\Order\Exceptions\OrderNotFoundException;
use Colame\Order\Exceptions\InvalidStatusTransitionException;
use Colame\Order\Models\Order;
use Colame\Order\Aggregates\OrderAggregate;

/**
 * Order service implementation
 */
class OrderService extends BaseService implements OrderServiceInterface, ResourceMetadataInterface
{
    public function __construct(
        FeatureFlagInterface $features,
        private OrderRepositoryInterface $orderRepository,
        private OrderItemRepositoryInterface $orderItemRepository,
        private ItemRepositoryInterface $itemRepository,
        private OrderCalculationService $calculationService,
        private OrderValidationService $validationService,
        private EventSourcedOrderService $eventSourcedService,
    ) {
        parent::__construct($features);
    }

    /**
     * Create a new order
     */
    public function createOrder(CreateOrderData $data): OrderData
    {
        $this->logAction('Creating order', ['userId' => $data->userId, 'locationId' => $data->locationId]);

        // Validate order items
        $this->validationService->validateOrderItems($data->items->toArray());

        // Use event sourcing to create the order
        $orderUuid = $this->eventSourcedService->createOrder($data);
        
        // Get the order from projection
        $order = Order::where('uuid', $orderUuid)->first();
        
        if (!$order) {
            throw new OrderNotFoundException("Order {$orderUuid} not found after creation");
        }
        
        return OrderData::from($order);
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

        // Get order UUID
        $orderModel = Order::find($id);
        if (!$orderModel || !$orderModel->uuid) {
            throw new OrderNotFoundException("Order UUID not found for ID {$id}");
        }

        // Use event sourcing to update customer info
        $aggregate = OrderAggregate::retrieve($orderModel->uuid);
        
        // Update customer information if provided
        if (!($data->customerName instanceof \Spatie\LaravelData\Optional) ||
            !($data->customerPhone instanceof \Spatie\LaravelData\Optional) ||
            !($data->notes instanceof \Spatie\LaravelData\Optional)) {
            
            $aggregate->updateCustomerInfo(
                customerName: !($data->customerName instanceof \Spatie\LaravelData\Optional) ? $data->customerName : null,
                customerPhone: !($data->customerPhone instanceof \Spatie\LaravelData\Optional) ? $data->customerPhone : null,
                notes: !($data->notes instanceof \Spatie\LaravelData\Optional) ? $data->notes : null,
                updatedBy: request()->user()?->email ?? 'system'
            );
        }
        
        // Update items if provided
        if ($data->hasItemsUpdate()) {
            $updatedItems = [];
            $deletedItemIds = [];
            
            foreach ($data->items as $item) {
                if (isset($item['id']) && isset($item['quantity'])) {
                    if ($item['quantity'] > 0) {
                        $updatedItems[] = $item;
                    } else {
                        $deletedItemIds[] = $item['id'];
                    }
                }
            }
            
            if (!empty($updatedItems) || !empty($deletedItemIds)) {
                $aggregate->updateOrderItems(
                    updatedItems: $updatedItems,
                    deletedItemIds: $deletedItemIds,
                    updatedBy: request()->user()?->email ?? 'system'
                );
            }
        }
        
        $aggregate->persist();
        
        // Return updated order from projection
        return $this->orderRepository->find($id);
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

        // Mock data for now - in a real implementation, these would come from other services
        $user = $order->userId ? (object) [
            'id' => $order->userId,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'waiter',
        ] : null;
        
        $location = (object) [
            'id' => $order->locationId,
            'name' => $order->locationId === 1 ? 'Main Branch' : 'Downtown Branch',
            'address' => $order->locationId === 1 ? '123 Main St' : '456 Downtown Ave',
            'phone' => $order->locationId === 1 ? '555-0123' : '555-0456',
        ];
        
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
        return $this->transitionOrderStatus($id, 'confirmed', __('order.status.confirmed'));
    }

    /**
     * Mark order as preparing
     */
    public function startPreparingOrder(int $id): OrderData
    {
        return $this->transitionOrderStatus($id, 'preparing', __('order.status.preparing'));
    }

    /**
     * Mark order as ready
     */
    public function markOrderReady(int $id): OrderData
    {
        return $this->transitionOrderStatus($id, 'ready', __('order.status.ready'));
    }

    /**
     * Complete an order
     */
    public function completeOrder(int $id): OrderData
    {
        return $this->transitionOrderStatus($id, 'completed', __('order.status.completed'));
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

        // Get order UUID
        $orderModel = Order::find($id);
        if (!$orderModel || !$orderModel->uuid) {
            throw new OrderNotFoundException("Order UUID not found for ID {$id}");
        }
        
        // Use event sourcing to cancel
        $this->eventSourcedService->cancelOrder(
            $orderModel->uuid,
            $reason,
            request()->user()?->email ?? 'system'
        );
        
        return $this->orderRepository->find($id);
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
    public function getKitchenOrders(int $locationId): DataCollection
    {
        if (!$this->isFeatureEnabled('order.kitchen_display')) {
            return new DataCollection(OrderData::class, []);
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

        return $this->orderItemRepository->updateStatus($itemId, $status);
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
     * Transition order status using Model States dynamically
     */
    public function transitionOrderStatus(int $id, string $newStatus, ?string $reason = null): OrderData
    {
        $orderModel = Order::find($id);
        
        if (!$orderModel) {
            throw new OrderNotFoundException("Order {$id} not found");
        }

        // Refresh the model to ensure we have the latest state
        $orderModel->refresh();
        
        // Get current status value
        $currentStatus = $orderModel->status->getValue();
        
        $this->logAction('Transitioning order status', [
            'orderId' => $id,
            'from' => $currentStatus,
            'to' => $newStatus
        ]);

        // Map the string status to the state class
        $targetStateClass = $this->getStateClassFromString($newStatus);
        
        if (!$targetStateClass) {
            throw new InvalidStatusTransitionException("Unknown target status: {$newStatus}");
        }
        
        // Use the state's built-in method to check if transition is allowed
        if (!$orderModel->status->canTransitionTo($targetStateClass)) {
            // Special handling for event-sourced states that need progression
            $eventSourcedStates = ['started', 'items_added', 'items_validated', 'promotions_calculated', 'price_calculated'];
            
            if (in_array($currentStatus, $eventSourcedStates) && $newStatus === 'confirmed') {
                // Use event-sourced service which handles all intermediate state transitions
                if ($orderModel->uuid) {
                    $this->eventSourcedService->confirmOrder($orderModel->uuid, $orderModel->payment_method ?? 'cash');
                    return $this->orderRepository->find($id);
                }
            }
            
            // Get the list of allowed transitions for better error message
            $allowedStates = $orderModel->status->transitionableStates();
            $allowedStatusNames = array_map(function($stateClass) {
                return $this->getStringFromStateClass($stateClass);
            }, $allowedStates);
            
            throw new InvalidStatusTransitionException(
                "Cannot transition from '{$currentStatus}' to '{$newStatus}'. Allowed transitions: " . implode(', ', $allowedStatusNames)
            );
        }
        
        // Perform the transition using the state machine
        try {
            $orderModel->status->transitionTo($targetStateClass);
            $orderModel->save();
        } catch (\Exception $e) {
            throw new InvalidStatusTransitionException(
                "Failed to transition order status: {$e->getMessage()}"
            );
        }
        
        // Log the transition in event stream only for fully event-sourced orders
        // For mixed-mode orders (created via regular flow but have events), 
        // we skip event sourcing for status transitions to avoid conflicts
        if ($orderModel->uuid && false) { // Disabled for now - mixing modes causes issues
            // Also record in event stream if order has UUID
            try {
                $aggregate = OrderAggregate::retrieve($orderModel->uuid);
                $aggregate->transitionStatus(
                    $newStatus,
                    $reason,
                    request()->user()?->email ?? 'system'
                );
                $aggregate->persist();
            } catch (\Exception $e) {
                // Log but don't fail if event sourcing fails
                \Log::warning("Could not record status transition in event stream: " . $e->getMessage());
            }
        }
        
        // Don't manually create events here - let the event sourcing system handle it
        // This was causing duplicate events when orders were confirmed
        // The OrderAggregate and projectors handle event creation properly
        
        return $this->orderRepository->find($id);
    }
    
    /**
     * Map string status to state class
     */
    private function getStateClassFromString(string $status): ?string
    {
        $stateMap = [
            'draft' => \Colame\Order\States\DraftState::class,
            'started' => \Colame\Order\States\StartedState::class,
            'items_added' => \Colame\Order\States\ItemsAddedState::class,
            'items_validated' => \Colame\Order\States\ItemsValidatedState::class,
            'promotions_calculated' => \Colame\Order\States\PromotionsCalculatedState::class,
            'price_calculated' => \Colame\Order\States\PriceCalculatedState::class,
            'confirmed' => \Colame\Order\States\ConfirmedState::class,
            'preparing' => \Colame\Order\States\PreparingState::class,
            'ready' => \Colame\Order\States\ReadyState::class,
            'delivering' => \Colame\Order\States\DeliveringState::class,
            'delivered' => \Colame\Order\States\DeliveredState::class,
            'completed' => \Colame\Order\States\CompletedState::class,
            'cancelled' => \Colame\Order\States\CancelledState::class,
            'refunded' => \Colame\Order\States\RefundedState::class,
        ];
        
        return $stateMap[$status] ?? null;
    }
    
    /**
     * Get string representation from state class
     */
    private function getStringFromStateClass(string $stateClass): string
    {
        $className = class_basename($stateClass);
        $stateName = str_replace('State', '', $className);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $stateName));
    }

    /**
     * Update order items
     */
    private function updateOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            if (isset($item['id']) && isset($item['quantity'])) {
                if ($item['quantity'] > 0) {
                    $this->orderItemRepository->update($item['id'], ['quantity' => $item['quantity']]);
                } else {
                    $this->orderItemRepository->delete($item['id']);
                }
            }
        }
    }

    /**
     * Get item name from item repository
     */
    private function getItemName(int $itemId): string
    {
        $item = $this->itemRepository->find($itemId);
        return $item ? $item->name : "Unknown Item #{$itemId}";
    }

    /**
     * Get paginated orders with filters
     */
    public function getPaginatedOrders(array $filters, int $perPage = 20): PaginatedResourceData
    {
        $paginator = $this->orderRepository->paginateWithFilters($filters, $perPage);
        
        // Generate metadata for the resource
        $metadata = $this->getResourceMetadata()->toArray();
        
        return PaginatedResourceData::fromPaginator(
            $paginator,
            OrderData::class,
            $metadata
        );
    }

    /**
     * Get order statistics
     */
    public function getOrderStats(array $filters = []): array
    {
        $query = Order::query();

        // Apply same filters as listing
        if (!empty($filters['locationId'])) {
            $query->where('location_id', $filters['locationId']);
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
        
        // Today's orders count (always from today regardless of filters)
        $todayOrders = Order::query()->whereDate('created_at', today())->count();
        
        // Active orders (placed, confirmed, preparing, ready)
        $activeOrders = clone $query;
        $activeOrders = $activeOrders->whereIn('status', ['placed', 'confirmed', 'preparing', 'ready'])->count();
        
        // Ready to serve
        $readyToServe = clone $query;
        $readyToServe = $readyToServe->where('status', 'ready')->count();
        
        // Pending payment
        $pendingPayment = clone $query;
        $pendingPayment = $pendingPayment->where('payment_status', 'pending')->count();
        
        $revenueToday = clone $dateQuery;
        $revenueToday = $revenueToday->whereIn('status', ['completed', 'delivered'])
                                     ->sum('total');

        $avgOrderValue = $totalOrders > 0 ? $revenueToday / $totalOrders : 0;
        
        $completedOrders = clone $dateQuery;
        $completedOrders = $completedOrders->whereIn('status', ['completed', 'delivered'])->count();
        $completionRate = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;

        return [
            'totalOrders' => $totalOrders,
            'todayOrders' => $todayOrders,
            'activeOrders' => $activeOrders,
            'readyToServe' => $readyToServe,
            'pendingPayment' => $pendingPayment,
            'revenueToday' => $revenueToday,
            'averageOrderValue' => round($avgOrderValue, 2),
            'completionRate' => round($completionRate, 1),
        ];
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData(array $filters = []): array
    {
        $period = $filters['period'] ?? 'today';
        $locationId = $filters['locationId'] ?? null;

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
        $revenue = $revenue->whereIn('status', ['completed', 'delivered'])->sum('total');
        
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
                              ->sum('total');
            
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
            ->selectRaw('type, COUNT(*) as count, SUM(total) as revenue')
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

    /**
     * Get metadata for the resource
     */
    public function getResourceMetadata(array $context = []): ResourceMetadata
    {
        $columns = [];
        
        // General search filter (not a column)
        $columns['search'] = ColumnMetadata::text('search', 'Search', true, false)
            ->withFilter(FilterMetadata::search('search', 'Orders', 'Search orders...', 300));
        
        // Order Number column
        $columns['orderNumber'] = ColumnMetadata::text('orderNumber', 'Order', true, true);
        
        // Customer Name column
        $columns['customerName'] = ColumnMetadata::text('customerName', 'Customer', true, false);
        
        // Type column
        $columns['type'] = ColumnMetadata::enum('type', 'Type', $this->orderRepository->getFilterOptions('type'))
            ->withFilter(FilterMetadata::multiSelect(
                'type',
                'Type',
                $this->orderRepository->getFilterOptions('type'),
                'Filter by type',
                3
            ));
        
        // Status column with multi-select filter
        $columns['status'] = ColumnMetadata::enum('status', 'Status', $this->orderRepository->getFilterOptions('status'))
            ->withFilter(FilterMetadata::multiSelect(
                'status',
                'Status',
                $this->orderRepository->getFilterOptions('status'),
                'Filter by status',
                3
            ));
        
        // Items count column
        $columns['items'] = ColumnMetadata::number('items', 'Items', null, false);
        
        // Total Amount column
        $columns['totalAmount'] = ColumnMetadata::currency('totalAmount', 'Total', 'CLP', true);
        
        // Payment Status column
        $columns['paymentStatus'] = ColumnMetadata::enum(
            'paymentStatus',
            'Payment',
            $this->orderRepository->getFilterOptions('payment_status')
        );
        
        // Created At column
        $columns['createdAt'] = ColumnMetadata::dateTime('createdAt', 'Time', 'relative', true);
        
        // Location filter (not a column but a filter)
        $locationFilter = FilterMetadata::multiSelect(
            'locationId',
            'Location',
            [], // Should come from location service
            'Filter by location',
            3
        );
        
        // Date filter
        $dateFilter = FilterMetadata::date(
            'date',
            'Date',
            [
                ['label' => 'Today', 'value' => 'today'],
                ['label' => 'Yesterday', 'value' => 'yesterday'],
                ['label' => 'This Week', 'value' => 'week'],
                ['label' => 'This Month', 'value' => 'month'],
            ],
            'YYYY-MM-DD'
        );
        
        // Add the non-column filters
        $columns['locationId'] = ColumnMetadata::text('locationId', 'Location', false, false)
            ->withFilter($locationFilter);
            
        $columns['date'] = ColumnMetadata::text('date', 'Date', false, false)
            ->withFilter($dateFilter);
        
        return new ResourceMetadata(
            columns: ColumnMetadata::collect(array_values($columns), DataCollection::class),
            defaultFilters: ['search', 'status', 'type', 'locationId', 'date'],
            defaultSort: '-created_at',
            filterPresets: $this->getFilterPresets(),
            exportFormats: ['csv', 'excel', 'pdf'],
            actions: $this->getAvailableActions($context),
            bulkActions: ['cancel', 'export'],
            settings: [
                'refreshInterval' => 30000, // 30 seconds
                'pageSize' => 20,
            ],
            rowActions: true
        );
    }

    /**
     * Get filter presets for the resource
     */
    public function getFilterPresets(): array
    {
        return [
            new FilterPresetData(
                id: 'active',
                name: 'Active Orders',
                description: 'Orders that are currently being processed',
                filters: [
                    'status' => ['placed', 'confirmed', 'preparing', 'ready'],
                ],
                icon: 'clock'
            ),
            new FilterPresetData(
                id: 'today',
                name: "Today's Orders",
                description: 'All orders from today',
                filters: [
                    'date' => 'today',
                ],
                icon: 'calendar',
                isDefault: true
            ),
            new FilterPresetData(
                id: 'completed',
                name: 'Completed Orders',
                description: 'Successfully completed orders',
                filters: [
                    'status' => ['completed', 'delivered'],
                ],
                icon: 'check'
            ),
            new FilterPresetData(
                id: 'issues',
                name: 'Orders with Issues',
                description: 'Cancelled or refunded orders',
                filters: [
                    'status' => ['cancelled', 'refunded'],
                ],
                icon: 'alert'
            ),
        ];
    }

    /**
     * Get available actions for the resource
     */
    public function getAvailableActions(array $context = []): array
    {
        $actions = [
            [
                'id' => 'view',
                'label' => 'View Details',
                'icon' => 'eye',
                'route' => 'orders.show',
            ],
            [
                'id' => 'edit',
                'label' => 'Edit Order',
                'icon' => 'edit',
                'route' => 'orders.edit',
                'condition' => 'canBeModified',
            ],
            [
                'id' => 'receipt',
                'label' => 'Print Receipt',
                'icon' => 'receipt',
                'route' => 'orders.receipt',
            ],
            [
                'id' => 'cancel',
                'label' => 'Cancel Order',
                'icon' => 'trash',
                'route' => 'orders.cancel',
                'condition' => 'canBeCancelled',
                'confirmRequired' => true,
                'variant' => 'destructive',
            ],
        ];
        
        // Filter actions based on user permissions
        if (!empty($context['user'])) {
            // TODO: Check user permissions
        }
        
        return $actions;
    }

    /**
     * Get export configuration for the resource
     */
    public function getExportConfiguration(): array
    {
        return [
            'formats' => [
                'csv' => [
                    'label' => 'CSV',
                    'extension' => 'csv',
                    'mimeType' => 'text/csv',
                ],
                'excel' => [
                    'label' => 'Excel',
                    'extension' => 'xlsx',
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                'pdf' => [
                    'label' => 'PDF',
                    'extension' => 'pdf',
                    'mimeType' => 'application/pdf',
                ],
            ],
            'columns' => [
                'orderNumber' => 'Order Number',
                'customerName' => 'Customer',
                'type' => 'Type',
                'status' => 'Status',
                'totalAmount' => 'Total',
                'paymentStatus' => 'Payment',
                'createdAt' => 'Date',
            ],
        ];
    }
}