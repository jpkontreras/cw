<?php

declare(strict_types=1);

namespace Colame\Order\Repositories;

use App\Core\Traits\ValidatesPagination;
use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\OrderItemData;
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderItem;
use Colame\Order\Models\OrderStatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Order repository implementation
 */
class OrderRepository implements OrderRepositoryInterface
{
    use ValidatesPagination;
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
    public function all(): DataCollection
    {
        return OrderData::collect(Order::all(), DataCollection::class);
    }

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): DataCollection
    {
        return OrderData::collect(
            Order::where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get(),
            DataCollection::class
        );
    }

    /**
     * Get orders for a specific location
     */
    public function getByLocation(int $locationId): DataCollection
    {
        return OrderData::collect(
            Order::where('location_id', $locationId)
                ->orderBy('created_at', 'desc')
                ->get(),
            DataCollection::class
        );
    }

    /**
     * Get orders for a specific user
     */
    public function getByUser(int $userId): DataCollection
    {
        return OrderData::collect(
            Order::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get(),
            DataCollection::class
        );
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
    public function getActiveKitchenOrders(int $locationId): DataCollection
    {
        $orders = Order::where('location_id', $locationId)
            ->whereIn('status', [
                Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
            ])
            ->with('items')
            ->orderBy('placed_at', 'asc')
            ->get();
        
        return OrderData::collect($orders, DataCollection::class);
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
        if ($includeItems && !$order->relationLoaded('items')) {
            $order->load('items');
        }
        
        return OrderData::fromModel($order);
    }

    /**
     * Transform a model to DTO
     * 
     * Implementation of BaseRepositoryInterface::toData
     */
    public function toData(mixed $model): ?Data
    {
        if (!$model instanceof Order) {
            return null;
        }
        
        return $this->modelToData($model);
    }

    /**
     * Get paginated entities
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        return Order::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Apply filters to query
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Status filter
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Type filter
        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $query->whereIn('type', $filters['type']);
            } else {
                $query->where('type', $filters['type']);
            }
        }

        // Location filter
        if (!empty($filters['location_id'])) {
            if (is_array($filters['location_id'])) {
                $query->whereIn('location_id', $filters['location_id']);
            } else {
                $query->where('location_id', $filters['location_id']);
            }
        }

        // Date filter
        if (!empty($filters['date'])) {
            $this->applyDateFilter($query, $filters['date']);
        }

        // Date range filter
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(order_number) LIKE LOWER(?)', ["%{$search}%"])
                  ->orWhereRaw('LOWER(customer_name) LIKE LOWER(?)', ["%{$search}%"])
                  ->orWhereRaw('LOWER(customer_phone) LIKE LOWER(?)', ["%{$search}%"])
                  ->orWhereRaw('LOWER(customer_email) LIKE LOWER(?)', ["%{$search}%"]);
            });
        }

        // Column-specific text filters
        if (!empty($filters['orderNumber'])) {
            $query->whereRaw('LOWER(order_number) LIKE LOWER(?)', ["%{$filters['orderNumber']}%"]);
        }

        if (!empty($filters['customerName'])) {
            $query->whereRaw('LOWER(customer_name) LIKE LOWER(?)', ["%{$filters['customerName']}%"]);
        }

        // Payment status filter
        if (!empty($filters['paymentStatus'])) {
            if (is_array($filters['paymentStatus'])) {
                $query->whereIn('payment_status', $filters['paymentStatus']);
            } else {
                $query->where('payment_status', $filters['paymentStatus']);
            }
        }

        // Sort
        if (!empty($filters['sort'])) {
            $this->applySorting($query, $filters['sort']);
        } else {
            // Default sort
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Get paginated entities with filters
     */
    public function paginateWithFilters(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        // Validate perPage parameter
        $perPage = $this->validatePerPage($perPage);
        
        $query = Order::query();
        
        // Load relationships if needed
        if ($this->shouldIncludeRelations($filters)) {
            $query->with(['items']);
        }

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Get available filter options for a field
     */
    public function getFilterOptions(string $field): array
    {
        switch ($field) {
            case 'status':
                return [
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'placed', 'label' => 'Placed'],
                    ['value' => 'confirmed', 'label' => 'Confirmed'],
                    ['value' => 'preparing', 'label' => 'Preparing'],
                    ['value' => 'ready', 'label' => 'Ready'],
                    ['value' => 'delivering', 'label' => 'Delivering'],
                    ['value' => 'delivered', 'label' => 'Delivered'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                    ['value' => 'refunded', 'label' => 'Refunded'],
                ];
                
            case 'type':
                return [
                    ['value' => 'dine_in', 'label' => 'Dine In'],
                    ['value' => 'takeout', 'label' => 'Takeout'],
                    ['value' => 'delivery', 'label' => 'Delivery'],
                    ['value' => 'catering', 'label' => 'Catering'],
                ];
                
            case 'payment_status':
                return [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'partial', 'label' => 'Partial'],
                    ['value' => 'paid', 'label' => 'Paid'],
                    ['value' => 'refunded', 'label' => 'Refunded'],
                ];
                
            default:
                return [];
        }
    }

    /**
     * Get searchable fields
     */
    public function getSearchableFields(): array
    {
        return ['order_number', 'customer_name', 'customer_phone', 'customer_email'];
    }

    /**
     * Get sortable fields
     */
    public function getSortableFields(): array
    {
        return [
            'orderNumber',
            'customerName',
            'status',
            'type',
            'totalAmount',
            'paymentStatus',
            'createdAt',
            'updatedAt',
            'placedAt',
            'completedAt',
        ];
    }

    /**
     * Get default sort configuration
     */
    public function getDefaultSort(): array
    {
        return [
            'field' => 'created_at',
            'direction' => 'desc',
        ];
    }

    /**
     * Apply date filter based on preset
     */
    private function applyDateFilter(Builder $query, string $preset): void
    {
        switch ($preset) {
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
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'quarter':
                $query->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()]);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting(Builder $query, string $sort): void
    {
        // Parse sort string (e.g., "-createdAt,orderNumber")
        $sorts = explode(',', $sort);
        
        foreach ($sorts as $sortField) {
            $sortField = trim($sortField);
            if (empty($sortField)) {
                continue;
            }
            
            $direction = 'asc';
            if (strpos($sortField, '-') === 0) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }
            
            // laravel-data will handle camelCase to snake_case mapping
            if (in_array($sortField, $this->getSortableFields())) {
                // Convert camelCase to snake_case for database query
                $dbField = \Illuminate\Support\Str::snake($sortField);
                $query->orderBy($dbField, $direction);
            }
        }
    }

    /**
     * Check if relations should be included
     */
    private function shouldIncludeRelations(array $filters): bool
    {
        return !empty($filters['include']) && in_array('items', (array) $filters['include']);
    }
}