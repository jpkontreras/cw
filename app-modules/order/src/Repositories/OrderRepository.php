<?php

declare(strict_types=1);

namespace Colame\Order\Repositories;

use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\OrderItemData;
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderItem;
use Colame\Order\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;

/**
 * Order repository implementation
 */
class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Find order by ID
     */
    public function find(int $id): ?OrderData
    {
        $order = Order::find($id);
        
        if (!$order) {
            return null;
        }

        return $this->modelToData($order);
    }

    /**
     * Find order by ID or throw exception
     */
    public function findOrFail(int $id): OrderData
    {
        $order = Order::findOrFail($id);
        return $this->modelToData($order);
    }

    /**
     * Get all orders
     */
    public function all(): array
    {
        return Order::all()
            ->map(fn($order) => $this->modelToData($order))
            ->toArray();
    }

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): array
    {
        return Order::where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($order) => $this->modelToData($order))
            ->toArray();
    }

    /**
     * Get orders for a specific location
     */
    public function getByLocation(int $locationId): array
    {
        return Order::where('location_id', $locationId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($order) => $this->modelToData($order))
            ->toArray();
    }

    /**
     * Get orders for a specific user
     */
    public function getByUser(int $userId): array
    {
        return Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($order) => $this->modelToData($order))
            ->toArray();
    }

    /**
     * Create new order
     */
    public function create(array $data): OrderData
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create($data);
            
            // Create initial status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => '',
                'to_status' => $order->status,
                'user_id' => $data['user_id'] ?? null,
                'reason' => 'Order created',
            ]);
            
            return $this->modelToData($order);
        });
    }

    /**
     * Update order
     */
    public function update(int $id, array $data): bool
    {
        return Order::where('id', $id)->update($data) > 0;
    }

    /**
     * Update order status
     */
    public function updateStatus(int $id, string $status, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($id, $status, $reason) {
            $order = Order::find($id);
            
            if (!$order) {
                return false;
            }
            
            $oldStatus = $order->status;
            $order->status = $status;
            
            if ($reason && $status === Order::STATUS_CANCELLED) {
                $order->cancel_reason = $reason;
            }
            
            $saved = $order->save();
            
            if ($saved) {
                // Record status change
                OrderStatusHistory::create([
                    'order_id' => $id,
                    'from_status' => $oldStatus,
                    'to_status' => $status,
                    'user_id' => auth()->id(),
                    'reason' => $reason,
                ]);
            }
            
            return $saved;
        });
    }

    /**
     * Delete order
     */
    public function delete(int $id): bool
    {
        return Order::destroy($id) > 0;
    }

    /**
     * Check if order exists
     */
    public function exists(int $id): bool
    {
        return Order::where('id', $id)->exists();
    }

    /**
     * Get active orders for kitchen display
     */
    public function getActiveKitchenOrders(int $locationId): array
    {
        return Order::where('location_id', $locationId)
            ->whereIn('status', [
                Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
            ])
            ->orderBy('placed_at', 'asc')
            ->get()
            ->map(fn($order) => $this->modelToData($order, true))
            ->toArray();
    }

    /**
     * Get order statistics for a date range
     */
    public function getStatistics(int $locationId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $stats = Order::where('location_id', $locationId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status = ? THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status = ? THEN 1 END) as cancelled_orders,
                AVG(total_amount) as average_order_value,
                SUM(total_amount) as total_revenue,
                AVG(TIMESTAMPDIFF(MINUTE, placed_at, completed_at)) as average_completion_time
            ', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])
            ->first();
        
        return [
            'total_orders' => (int) $stats->total_orders,
            'completed_orders' => (int) $stats->completed_orders,
            'cancelled_orders' => (int) $stats->cancelled_orders,
            'average_order_value' => (float) $stats->average_order_value,
            'total_revenue' => (float) $stats->total_revenue,
            'average_completion_time' => (int) $stats->average_completion_time,
            'completion_rate' => $stats->total_orders > 0 
                ? round(($stats->completed_orders / $stats->total_orders) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Convert model to data transfer object
     */
    private function modelToData(Order $order, bool $includeItems = false): OrderData
    {
        $data = [
            'id' => $order->id,
            'userId' => $order->user_id,
            'locationId' => $order->location_id,
            'status' => $order->status,
            'subtotal' => $order->subtotal,
            'taxAmount' => $order->tax_amount,
            'discountAmount' => $order->discount_amount,
            'totalAmount' => $order->total_amount,
            'notes' => $order->notes,
            'cancelReason' => $order->cancel_reason,
            'customerName' => $order->customer_name,
            'customerPhone' => $order->customer_phone,
            'metadata' => $order->metadata,
            'placedAt' => $order->placed_at,
            'confirmedAt' => $order->confirmed_at,
            'preparingAt' => $order->preparing_at,
            'readyAt' => $order->ready_at,
            'completedAt' => $order->completed_at,
            'cancelledAt' => $order->cancelled_at,
            'createdAt' => $order->created_at,
            'updatedAt' => $order->updated_at,
        ];

        if ($includeItems) {
            $items = OrderItem::where('order_id', $order->id)->get();
            $data['items'] = OrderItemData::collection($items);
        }

        return OrderData::from($data);
    }
}