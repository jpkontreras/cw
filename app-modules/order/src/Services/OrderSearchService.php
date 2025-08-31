<?php

namespace Colame\Order\Services;

use App\Core\Contracts\ModuleSearchInterface;
use App\Core\Data\SearchResultData;
use Colame\Order\Contracts\OrderSearchInterface;
use Colame\Order\Data\OrderSearchData;
use Colame\Order\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Scout\Builder;

class OrderSearchService implements OrderSearchInterface, ModuleSearchInterface
{
    /**
     * Search orders using Scout/MeiliSearch.
     */
    public function search(string $query, array $filters = []): SearchResultData
    {
        $searchId = Str::uuid()->toString();
        $startTime = microtime(true);
        
        // Build Scout search query
        $searchBuilder = Order::search($query);
        
        // Apply filters
        $searchBuilder = $this->applyFilters($searchBuilder, $filters);
        
        // Execute search
        $results = $searchBuilder->paginate($filters['per_page'] ?? 20);
        
        // Transform to DTOs
        $items = OrderSearchData::collection(
            $results->map(function ($order) use ($query) {
                $dto = OrderSearchData::from($order);
                $dto->searchScore = $this->calculateRelevanceScore($order, $query);
                $dto->matchReason = $this->determineMatchReason($order, $query);
                return $dto;
            })
        );
        
        // Get facets for filtering
        $facets = $this->getFacets($query);
        
        // Get suggestions
        $suggestions = $this->getSuggestions($query);
        
        $searchTime = microtime(true) - $startTime;
        
        return new SearchResultData(
            items: $items,
            query: $query,
            searchId: $searchId,
            total: $results->total(),
            facets: $facets,
            suggestions: $suggestions,
            searchTime: $searchTime
        );
    }
    
    /**
     * Search orders by date range.
     */
    public function searchByDateRange(\DateTime $from, \DateTime $to, array $filters = []): SearchResultData
    {
        $searchId = Str::uuid()->toString();
        
        $query = Order::whereBetween('created_at', [$from, $to]);
        
        // Apply additional filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        $results = $query->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 20);
        
        $items = OrderSearchData::collection($results->items());
        
        return new SearchResultData(
            items: $items,
            query: "Date range: {$from->format('Y-m-d')} to {$to->format('Y-m-d')}",
            searchId: $searchId,
            total: $results->total(),
            facets: [],
            suggestions: []
        );
    }
    
    /**
     * Get order suggestions.
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        $suggestions = [];
        
        // Suggest by order number
        if (preg_match('/^[A-Z0-9#-]+$/i', $query)) {
            $orders = Order::where('order_number', 'LIKE', $query . '%')
                ->limit($limit)
                ->pluck('order_number')
                ->toArray();
            
            foreach ($orders as $orderNumber) {
                $suggestions[] = [
                    'type' => 'order_number',
                    'value' => $orderNumber,
                    'label' => "Order #{$orderNumber}"
                ];
            }
        }
        
        // Suggest by customer name
        if (strlen($query) >= 2) {
            $customers = Order::where('customer_name', 'LIKE', $query . '%')
                ->distinct()
                ->limit($limit)
                ->pluck('customer_name')
                ->toArray();
            
            foreach ($customers as $name) {
                $suggestions[] = [
                    'type' => 'customer',
                    'value' => $name,
                    'label' => $name
                ];
            }
        }
        
        // Suggest by phone
        if (preg_match('/^\d+/', $query)) {
            $phones = Order::where('customer_phone', 'LIKE', $query . '%')
                ->distinct()
                ->limit($limit)
                ->pluck('customer_phone')
                ->toArray();
            
            foreach ($phones as $phone) {
                $suggestions[] = [
                    'type' => 'phone',
                    'value' => $phone,
                    'label' => "Phone: {$phone}"
                ];
            }
        }
        
        return array_slice($suggestions, 0, $limit);
    }
    
    /**
     * Get searchable fields configuration.
     */
    public function getSearchableFields(): array
    {
        return [
            'order_number' => ['weight' => 10, 'exact_match' => true],
            'customer_name' => ['weight' => 8],
            'customer_phone' => ['weight' => 7],
            'customer_email' => ['weight' => 6],
            'table_number' => ['weight' => 5],
            'notes' => ['weight' => 3],
            'special_instructions' => ['weight' => 2],
        ];
    }
    
    /**
     * Get filterable fields configuration.
     */
    public function getFilterableFields(): array
    {
        return [
            'status' => ['type' => 'enum', 'values' => ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled']],
            'type' => ['type' => 'enum', 'values' => ['dine_in', 'takeout', 'delivery']],
            'payment_status' => ['type' => 'enum', 'values' => ['pending', 'paid', 'partial', 'refunded']],
            'location_id' => ['type' => 'numeric'],
            'waiter_id' => ['type' => 'numeric'],
            'total_amount' => ['type' => 'range'],
            'created_at' => ['type' => 'date_range'],
        ];
    }
    
    /**
     * Get sortable fields configuration.
     */
    public function getSortableFields(): array
    {
        return [
            'order_number' => 'Order Number',
            'created_at' => 'Date Created',
            'total_amount' => 'Total Amount',
            'status' => 'Status',
            'customer_name' => 'Customer Name',
        ];
    }
    
    /**
     * Record order selection from search.
     */
    public function recordSelection(string $searchId, mixed $entityId): void
    {
        // Record the selection
        DB::table('order_search_history')->insert([
            'search_id' => $searchId,
            'order_id' => $entityId,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
        
        // Update order view count for popularity
        Order::where('id', $entityId)->increment('view_count');
    }
    
    /**
     * Apply filters to Scout builder.
     */
    private function applyFilters(Builder $builder, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        
        if (!empty($filters['type'])) {
            $builder->where('type', $filters['type']);
        }
        
        if (!empty($filters['payment_status'])) {
            $builder->where('payment_status', $filters['payment_status']);
        }
        
        if (!empty($filters['location_id'])) {
            $builder->where('location_id', $filters['location_id']);
        }
        
        if (!empty($filters['min_amount'])) {
            $builder->where('total_amount', '>=', $filters['min_amount']);
        }
        
        if (!empty($filters['max_amount'])) {
            $builder->where('total_amount', '<=', $filters['max_amount']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder->where('created_at', '<=', $filters['date_to']);
        }
        
        return $builder;
    }
    
    /**
     * Get facets for filtering.
     */
    private function getFacets(string $query): array
    {
        return [
            'status' => $this->getStatusFacets(),
            'type' => $this->getTypeFacets(),
            'payment_status' => $this->getPaymentStatusFacets(),
            'date_ranges' => $this->getDateRangeFacets(),
        ];
    }
    
    /**
     * Get status facets.
     */
    private function getStatusFacets(): array
    {
        return Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
    
    /**
     * Get type facets.
     */
    private function getTypeFacets(): array
    {
        return Order::select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }
    
    /**
     * Get payment status facets.
     */
    private function getPaymentStatusFacets(): array
    {
        return Order::select('payment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status')
            ->toArray();
    }
    
    /**
     * Get date range facets.
     */
    private function getDateRangeFacets(): array
    {
        return [
            'today' => Order::whereDate('created_at', today())->count(),
            'yesterday' => Order::whereDate('created_at', today()->subDay())->count(),
            'this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => Order::whereMonth('created_at', now()->month)->count(),
        ];
    }
    
    /**
     * Calculate relevance score for an order.
     */
    private function calculateRelevanceScore(Order $order, string $query): float
    {
        $score = 0;
        $query = strtolower($query);
        
        // Exact order number match
        if (strtolower($order->order_number) === $query) {
            $score += 100;
        } elseif (str_contains(strtolower($order->order_number), $query)) {
            $score += 50;
        }
        
        // Customer name match
        if ($order->customer_name && str_contains(strtolower($order->customer_name), $query)) {
            $score += 30;
        }
        
        // Phone match
        if ($order->customer_phone && str_contains($order->customer_phone, $query)) {
            $score += 40;
        }
        
        // Recent orders get a boost
        $daysSinceOrder = now()->diffInDays($order->created_at);
        if ($daysSinceOrder <= 1) {
            $score += 20;
        } elseif ($daysSinceOrder <= 7) {
            $score += 10;
        }
        
        return $score;
    }
    
    /**
     * Determine why an order matched the search.
     */
    private function determineMatchReason(Order $order, string $query): string
    {
        $query = strtolower($query);
        
        if (str_contains(strtolower($order->order_number), $query)) {
            return 'order_number';
        }
        
        if ($order->customer_name && str_contains(strtolower($order->customer_name), $query)) {
            return 'customer_name';
        }
        
        if ($order->customer_phone && str_contains($order->customer_phone, $query)) {
            return 'phone_number';
        }
        
        if ($order->table_number && str_contains($order->table_number, $query)) {
            return 'table_number';
        }
        
        return 'content';
    }
}