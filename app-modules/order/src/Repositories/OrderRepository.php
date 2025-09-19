<?php

declare(strict_types=1);

namespace Colame\Order\Repositories;

use App\Core\Traits\ValidatesPagination;
use App\Core\Data\PaginatedResourceData;
use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Data\OrderData;
use Colame\Order\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Order repository implementation for event-sourced orders
 * Read-only operations on projected read models
 */
class OrderRepository implements OrderRepositoryInterface
{
    use ValidatesPagination;

    /**
     * Find order by ID
     */
    public function find(string $id): ?OrderData
    {
        $order = Order::with(['items'])->find($id);
        
        if (!$order) {
            return null;
        }

        return OrderData::fromModel($order);
    }

    /**
     * Find order by ID or throw exception
     */
    public function findOrFail(string $id): OrderData
    {
        $order = Order::with(['items'])->findOrFail($id);
        return OrderData::fromModel($order);
    }

    /**
     * Get all orders
     */
    public function all(): DataCollection
    {
        return OrderData::collection(Order::with(['items'])->get());
    }

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): DataCollection
    {
        return OrderData::collection(
            Order::with(['items'])
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    /**
     * Get orders for a specific location
     */
    public function getByLocation(int $locationId): DataCollection
    {
        return OrderData::collection(
            Order::where('location_id', $locationId)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    /**
     * Get orders for a specific user
     */
    public function getByUser(int $userId): DataCollection
    {
        return OrderData::collection(
            Order::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    /**
     * Create new order
     * @throws \RuntimeException Event-sourced orders cannot be created directly
     */
    public function create(array $data): OrderData
    {
        throw new \RuntimeException(
            'Orders must be created through event sourcing. Use OrderSession aggregate to initiate orders.'
        );
    }

    /**
     * Update order
     * @throws \RuntimeException Event-sourced orders cannot be updated directly
     */
    public function update(string $id, array $data): bool
    {
        throw new \RuntimeException(
            'Orders must be updated through event sourcing. Use OrderSession aggregate to modify orders.'
        );
    }

    /**
     * Update order status
     * @throws \RuntimeException Event-sourced orders cannot have status updated directly
     */
    public function updateStatus(string $id, string $status, ?string $reason = null): bool
    {
        throw new \RuntimeException(
            'Order status must be updated through event sourcing. Use OrderSession aggregate for status transitions.'
        );
    }

    /**
     * Delete order
     * @throws \RuntimeException Orders should not be deleted
     */
    public function delete(string $id): bool
    {
        throw new \RuntimeException(
            'Orders cannot be deleted. Use cancellation through event sourcing instead.'
        );
    }

    /**
     * Check if order exists
     */
    public function exists(string $id): bool
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
                'confirmed',
                'preparing',
                'ready',
            ])
            ->with('items')
            ->orderBy('placed_at', 'asc')
            ->get();
        
        return OrderData::collection($orders);
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
                AVG(total) as average_order_value,
                SUM(total) as total_revenue,
                AVG(TIMESTAMPDIFF(MINUTE, placed_at, completed_at)) as average_completion_time
            ', ['completed', 'cancelled'])
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
     * Paginate orders with filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): mixed
    {
        $perPage = $this->validatePerPage($perPage);
        
        $query = Order::query();
        
        // Always load items relation
        $query->with(['items']);

        // Apply filters
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }
    
    /**
     * Get paginated orders with metadata
     */
    public function getPaginatedOrders(array $filters, int $perPage = 20): PaginatedResourceData
    {
        $paginator = $this->paginateWithFilters($filters, $perPage);
        
        // Generate metadata for the resource
        $metadata = $this->getResourceMetadata();
        
        return PaginatedResourceData::fromPaginator(
            $paginator,
            OrderData::class,
            $metadata
        );
    }
    
    /**
     * Get resource metadata for data table
     */
    private function getResourceMetadata(): array
    {
        return [
            'columns' => [
                'orderNumber' => [
                    'key' => 'orderNumber',
                    'label' => 'Order #',
                    'type' => 'text',
                    'sortable' => true,
                    'visible' => true,
                ],
                'status' => [
                    'key' => 'status',
                    'label' => 'Status',
                    'type' => 'badge',
                    'sortable' => true,
                    'visible' => true,
                    'filter' => [
                        'type' => 'select',
                        'options' => [
                            ['value' => 'draft', 'label' => 'Draft'],
                            ['value' => 'started', 'label' => 'Started'],
                            ['value' => 'placed', 'label' => 'Placed'],
                            ['value' => 'confirmed', 'label' => 'Confirmed'],
                            ['value' => 'preparing', 'label' => 'Preparing'],
                            ['value' => 'ready', 'label' => 'Ready'],
                            ['value' => 'completed', 'label' => 'Completed'],
                            ['value' => 'cancelled', 'label' => 'Cancelled'],
                        ],
                    ],
                ],
                'type' => [
                    'key' => 'type',
                    'label' => 'Type',
                    'type' => 'text',
                    'sortable' => true,
                    'visible' => true,
                    'filter' => [
                        'type' => 'select',
                        'options' => [
                            ['value' => 'dine_in', 'label' => 'Dine In'],
                            ['value' => 'takeout', 'label' => 'Takeout'],
                            ['value' => 'delivery', 'label' => 'Delivery'],
                        ],
                    ],
                ],
                'customerName' => [
                    'key' => 'customerName',
                    'label' => 'Customer',
                    'type' => 'text',
                    'sortable' => true,
                    'visible' => true,
                ],
                'totalAmount' => [
                    'key' => 'totalAmount',
                    'label' => 'Total',
                    'type' => 'currency',
                    'sortable' => true,
                    'visible' => true,
                ],
                'paymentStatus' => [
                    'key' => 'paymentStatus',
                    'label' => 'Payment',
                    'type' => 'badge',
                    'sortable' => true,
                    'visible' => true,
                    'filter' => [
                        'type' => 'select',
                        'options' => [
                            ['value' => 'pending', 'label' => 'Pending'],
                            ['value' => 'paid', 'label' => 'Paid'],
                            ['value' => 'refunded', 'label' => 'Refunded'],
                        ],
                    ],
                ],
                'createdAt' => [
                    'key' => 'createdAt',
                    'label' => 'Created',
                    'type' => 'date',
                    'sortable' => true,
                    'visible' => true,
                ],
            ],
            'searchable' => ['orderNumber', 'customerName', 'customerPhone'],
            'defaultSort' => '-createdAt',
            'actions' => ['view', 'edit', 'cancel'],
        ];
    }

    /**
     * Get orders for dashboard
     */
    public function getDashboardOrders(int $locationId, array $filters = []): DataCollection
    {
        $query = Order::where('location_id', $locationId);
        
        // Apply dashboard-specific filters
        if (!empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }
        
        if (!empty($filters['type'])) {
            $query->whereIn('type', (array) $filters['type']);
        }
        
        // Default to recent orders
        $query->orderBy('created_at', 'desc')
              ->limit($filters['limit'] ?? 50);
        
        return OrderData::collection($query->get());
    }

    /**
     * Get today's orders for a location
     */
    public function getTodaysOrders(int $locationId): DataCollection
    {
        return OrderData::collection(
            Order::where('location_id', $locationId)
                ->whereDate('created_at', today())
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    /**
     * Apply filters to query
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        // Status filter
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } elseif (str_contains($filters['status'], ',')) {
                $statuses = array_map('trim', explode(',', $filters['status']));
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Type filter
        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $query->whereIn('type', $filters['type']);
            } elseif (str_contains($filters['type'], ',')) {
                $types = array_map('trim', explode(',', $filters['type']));
                $query->whereIn('type', $types);
            } else {
                $query->where('type', $filters['type']);
            }
        }

        // Location filter
        if (!empty($filters['locationId'])) {
            $query->where('location_id', $filters['locationId']);
        }

        // Date filters
        if (!empty($filters['date'])) {
            $this->applyDateFilter($query, $filters['date']);
        }

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

        // Payment status filter
        if (!empty($filters['paymentStatus'])) {
            $query->where('payment_status', $filters['paymentStatus']);
        }

        // Sort
        if (!empty($filters['sort'])) {
            $this->applySorting($query, $filters['sort']);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
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
            
            $dbField = \Illuminate\Support\Str::snake($sortField);
            $query->orderBy($dbField, $direction);
        }
    }
}