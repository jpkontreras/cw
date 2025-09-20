<?php

declare(strict_types=1);

namespace Colame\Order\Services;

use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Aggregates\OrderSession as OrderSessionAggregate;
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderSession;
use Colame\Order\Models\OrderStatusHistory;
use Colame\Order\Models\OrderItem;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\CreateOrderData;
use App\Core\Data\PaginatedResourceData;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * Create a new order directly (without session)
     */
    public function createOrder(CreateOrderData $data): OrderData
    {
        return DB::transaction(function () use ($data) {
            // Generate order ID and order number
            $orderId = Str::uuid()->toString();
            $orderNumber = 'ORD-' . strtoupper(Str::random(8));

            // Calculate totals
            $subtotal = 0;
            foreach ($data->items as $item) {
                $subtotal += $item->unitPrice * $item->quantity;
            }

            $tax = (int) ($subtotal * 0.10); // 10% tax rate - should come from config
            $total = $subtotal + $tax;

            // Create order
            $order = Order::create([
                'id' => $orderId,
                'order_number' => $orderNumber,
                'user_id' => $data->userId ?? auth()->id(),
                'location_id' => $data->sessionLocationId ?? 1, // Default location
                'currency' => 'USD',
                'status' => 'placed',
                'type' => $data->type,
                'priority' => 'normal',
                'customer_name' => $data->customerName,
                'customer_phone' => $data->customerPhone,
                'customer_email' => $data->customerEmail,
                'delivery_address' => $data->deliveryAddress,
                'table_number' => $data->tableNumber,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'tip' => 0,
                'discount' => 0,
                'total' => $total,
                'payment_status' => 'pending',
                'notes' => $data->notes,
                'special_instructions' => $data->specialInstructions,
                'metadata' => $data->metadata,
                'view_count' => 0,
                'modification_count' => 0,
                'placed_at' => now(),
            ]);

            // Create order items
            foreach ($data->items as $itemData) {
                OrderItem::create([
                    'id' => Str::uuid()->toString(),
                    'order_id' => $orderId,
                    'item_id' => $itemData->itemId,
                    'item_name' => $itemData->name ?? 'Item ' . $itemData->itemId,
                    'base_item_name' => $itemData->name ?? 'Item ' . $itemData->itemId,
                    'quantity' => $itemData->quantity,
                    'base_price' => $itemData->unitPrice,
                    'unit_price' => $itemData->unitPrice,
                    'modifiers_total' => 0,
                    'total_price' => $itemData->unitPrice * $itemData->quantity,
                    'status' => 'pending',
                    'kitchen_status' => 'pending',
                    'notes' => $itemData->notes,
                    'special_instructions' => $itemData->specialInstructions,
                    'modifiers' => $itemData->modifiers ?? [],
                    'modifier_count' => count($itemData->modifiers ?? []),
                    'metadata' => [],
                ]);
            }

            // Add initial status history
            OrderStatusHistory::create([
                'order_id' => $orderId,
                'from_status' => null,
                'to_status' => 'placed',
                'user_id' => auth()->id(),
                'reason' => 'Order created directly',
            ]);

            return OrderData::from($order->load(['items', 'statusHistory']));
        });
    }

    /**
     * Get paginated orders with filters
     */
    public function getPaginatedOrders(array $filters, int $perPage = 20): PaginatedResourceData
    {
        return $this->orderRepository->getPaginatedOrders($filters, $perPage);
    }

    /**
     * Find order by ID or order number
     */
    public function findOrderByIdOrNumber(string $identifier): ?OrderData
    {
        // Try finding by ID first
        $order = Order::find($identifier);

        // If not found by ID, try by order number
        if (!$order) {
            $order = Order::where('order_number', $identifier)->first();
        }

        if (!$order) {
            return null;
        }

        return OrderData::from($order->load(['items', 'statusHistory']));
    }

    /**
     * Confirm an order
     */
    public function confirmOrder(string $orderId): ?OrderData
    {
        $order = Order::find($orderId);

        if (!$order) {
            return null;
        }

        // Check if order can be confirmed
        if (!in_array($order->status, ['draft', 'started', 'placed'])) {
            return null;
        }

        // Use event sourcing if session exists
        if ($order->session_id) {
            try {
                OrderSessionAggregate::retrieve($order->session_id)
                    ->changeOrderStatus(
                        orderId: $orderId,
                        toStatus: 'confirmed',
                        userId: auth()->id(),
                        reason: 'Order confirmed via API'
                    )
                    ->persist();
            } catch (\Exception $e) {
                // If event sourcing fails, fall back to direct update
                $order->update(['status' => 'confirmed']);
            }
        } else {
            // Direct update if no session
            $order->update(['status' => 'confirmed']);
        }

        // Add to status history
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $order->status,
            'to_status' => 'confirmed',
            'user_id' => auth()->id(),
            'reason' => 'Order confirmed',
        ]);

        return OrderData::from($order->fresh()->load(['items', 'statusHistory']));
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(string $orderId, string $reason = 'Cancelled by user'): ?OrderData
    {
        $order = Order::find($orderId);

        if (!$order) {
            return null;
        }

        // Check if order can be cancelled
        if (in_array($order->status, ['completed', 'cancelled', 'delivered'])) {
            return null;
        }

        // Use event sourcing if session exists
        if ($order->session_id) {
            try {
                OrderSessionAggregate::retrieve($order->session_id)
                    ->abandonSession(
                        reason: $reason,
                        sessionDurationSeconds: 0,
                        lastActivity: 'order_cancelled'
                    )
                    ->persist();
            } catch (\Exception $e) {
                // If event sourcing fails, fall back to direct update
                $order->update(['status' => 'cancelled']);
            }
        } else {
            // Direct update if no session
            $order->update(['status' => 'cancelled']);
        }

        // Add to status history
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $order->status,
            'to_status' => 'cancelled',
            'user_id' => auth()->id(),
            'reason' => $reason,
        ]);

        return OrderData::from($order->fresh()->load(['items', 'statusHistory']));
    }

    /**
     * Change order status
     */
    public function changeOrderStatus(string $orderId, string $status, ?string $notes = null): ?OrderData
    {
        $order = Order::find($orderId);

        if (!$order) {
            return null;
        }

        $fromStatus = $order->status;

        // Use event sourcing if session exists
        if ($order->session_id) {
            try {
                OrderSessionAggregate::retrieve($order->session_id)
                    ->changeOrderStatus(
                        orderId: $orderId,
                        toStatus: $status,
                        userId: auth()->id(),
                        reason: $notes ?? 'Status changed via API'
                    )
                    ->persist();
            } catch (\Exception $e) {
                // If event sourcing fails, fall back to direct update
                $order->update(['status' => $status]);
            }
        } else {
            // Direct update if no session
            $order->update(['status' => $status]);
        }

        // Add to status history
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $status,
            'user_id' => auth()->id(),
            'reason' => $notes ?? 'Status changed',
        ]);

        return OrderData::from($order->fresh()->load(['items', 'statusHistory']));
    }

    /**
     * Get order state at a specific timestamp
     */
    public function getOrderStateAtTimestamp(string $orderId, Carbon $timestamp): ?array
    {
        $order = Order::find($orderId);

        if (!$order || !$order->session_id) {
            return null;
        }

        // Get events up to the specified timestamp
        $events = EloquentStoredEvent::query()
            ->where('aggregate_uuid', $order->session_id)
            ->where('created_at', '<=', $timestamp)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        // Build state from events
        $state = [
            'order_id' => $order->id,
            'timestamp' => $timestamp->toIso8601String(),
            'events_count' => $events->count(),
            'status' => 'draft',
            'items' => [],
            'total' => 0,
        ];

        // Process events to build state
        foreach ($events as $event) {
            $eventClass = class_basename($event->event_class);
            $eventData = $event->event_properties;

            switch ($eventClass) {
                case 'OrderStarted':
                case 'OrderSessionInitiated':
                    $state['status'] = 'started';
                    break;

                case 'ItemAddedToOrder':
                case 'CartItemAdded':
                    $state['items'][] = [
                        'id' => $eventData['item_id'] ?? null,
                        'name' => $eventData['item_name'] ?? 'Unknown',
                        'quantity' => $eventData['quantity'] ?? 1,
                        'price' => $eventData['unit_price'] ?? 0,
                    ];
                    break;

                case 'OrderCheckedOut':
                case 'SessionConverted':
                    $state['status'] = 'placed';
                    break;

                case 'OrderStatusChanged':
                    $state['status'] = $eventData['to_status'] ?? $state['status'];
                    break;
            }
        }

        // Calculate total
        $state['total'] = array_reduce($state['items'], function ($carry, $item) {
            return $carry + ($item['price'] * $item['quantity']);
        }, 0);

        return $state;
    }

    /**
     * Get active kitchen orders for a location
     */
    public function getActiveKitchenOrders(int $locationId, int $limit = 50): \Illuminate\Support\Collection
    {
        return $this->orderRepository->getActiveKitchenOrders($locationId, $limit);
    }

    /**
     * Get order statistics
     */
    public function getOrderStats(array $filters = []): array
    {
        $query = Order::query();

        // Apply location filter if present
        if (!empty($filters['locationId'])) {
            $query->where('location_id', $filters['locationId']);
        }

        // Today's orders
        $todayOrders = (clone $query)->whereDate('created_at', today())->count();

        // Active orders (not completed or cancelled)
        $activeOrders = (clone $query)->whereIn('status', ['started', 'placed', 'confirmed', 'preparing', 'ready'])->count();

        // Ready to serve
        $readyToServe = (clone $query)->where('status', 'ready')->count();

        // Pending payment
        $pendingPayment = (clone $query)->where('payment_status', 'pending')->count();

        return [
            'todayOrders' => $todayOrders,
            'activeOrders' => $activeOrders,
            'readyToServe' => $readyToServe,
            'pendingPayment' => $pendingPayment,
        ];
    }
}