<?php

namespace Colame\Item\Services;

use App\Core\Contracts\ModuleSearchInterface;
use App\Core\Data\SearchResultData;
use Colame\Item\Contracts\ItemSearchInterface;
use Colame\Item\Data\ItemSearchData;
use Colame\Item\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Scout\Builder;
use Spatie\LaravelData\DataCollection;

class ItemSearchService implements ItemSearchInterface, ModuleSearchInterface
{
    /**
     * Search items using Scout/MeiliSearch.
     */
    public function search(string $query, array $filters = []): SearchResultData
    {
        $searchId = Str::uuid()->toString();
        $startTime = microtime(true);

        // If no query, return popular items
        if (empty($query)) {
            $popularItems = $this->getPopularItemsData($filters['limit'] ?? 20);
            // Convert Collection to DataCollection
            $items = new DataCollection(ItemSearchData::class, $popularItems);
        } else {
            // Build Scout search query
            $searchBuilder = Item::search($query);
            
            // Debug: Log filters being applied
            Log::info('Applying filters', [
                'query' => $query,
                'filters' => $filters
            ]);
            
            // Apply filters - SKIP FOR NOW TO DEBUG
            // $searchBuilder = $this->applyFilters($searchBuilder, $filters);
            
            // Test without filters first
            Log::info('Testing search without filters');

            // Execute search - use get() then manually paginate
            // There's an issue with Scout's paginate() not returning items properly
            $allResults = $searchBuilder->get();
            
            // Manually paginate the results
            $perPage = $filters['per_page'] ?? 20;
            $paginatedItems = $allResults->take($perPage);
            
            // Create a fake paginator for total count
            $results = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedItems,
                $allResults->count(),
                $perPage,
                1
            );
            
            // Debug: Log what we got
            Log::info('Scout search results', [
                'query' => $query,
                'total' => $results->total(),
                'items_count' => count($paginatedItems),
                'first_item' => $paginatedItems->first() ? [
                    'id' => $paginatedItems->first()->id ?? 'no id',
                    'name' => $paginatedItems->first()->name ?? 'no name',
                ] : 'no items'
            ]);

            // Add debugging to see what's in the collection
            if ($paginatedItems->isEmpty()) {
                Log::error('Paginated items is empty!', [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'collection_class' => get_class($paginatedItems),
                    'raw_items' => $results->items(),
                ]);

                // Try alternative method to get items
                $paginatedItems = collect($results->items());
                if ($paginatedItems->isEmpty()) {
                    // If still empty, try to re-fetch from database
                    $searchResults = $searchBuilder->get();
                    $paginatedItems = $searchResults->take($filters['per_page'] ?? 20);
                }
            }

            // Transform to DTOs
            $mappedItems = $paginatedItems->map(function ($item) use ($query) {
                $data = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'basePrice' => $item->base_price,
                    'category' => $item->category,
                    'description' => $item->description,
                    'sku' => $item->sku,
                    'isAvailable' => $item->is_available,
                    'isActive' => $item->is_active,
                    'preparationTime' => $item->preparation_time,
                    'stockQuantity' => $item->stock_quantity,
                    'image' => $item->image,
                    'isPopular' => ($item->order_frequency ?? 0) > 50,
                    'orderFrequency' => $item->order_frequency ?? 0,
                    'searchScore' => $this->calculateRelevanceScore($item, $query),
                    'matchReason' => $this->determineMatchReason($item, $query),
                ];
                return ItemSearchData::from($data);
            });
            // Convert Collection to DataCollection
            $items = new DataCollection(ItemSearchData::class, $mappedItems);
        }

        // Get facets for filtering
        $facets = $this->getFacets();

        // Get suggestions
        $suggestions = $this->getSuggestions($query);

        $searchTime = microtime(true) - $startTime;

        return new SearchResultData(
            items: $items,
            query: $query,
            searchId: $searchId,
            total: isset($results) ? $results->total() : $items->count(),
            facets: $facets,
            suggestions: $suggestions,
            searchTime: $searchTime
        );
    }

    /**
     * Get item suggestions.
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        if (empty($query)) return [];

        $suggestions = [];

        // Suggest by item name
        $items = Item::where('name', 'LIKE', $query . '%')
            ->where('is_active', true)
            ->where('is_available', true)
            ->limit($limit)
            ->get();

        foreach ($items as $item) {
            $suggestions[] = [
                'type' => 'item',
                'value' => $item->name,
                'label' => $item->name,
                'price' => $item->base_price,
            ];
        }

        // Suggest by category
        $categories = DB::table('items')
            ->select('category')
            ->where('category', 'LIKE', $query . '%')
            ->distinct()
            ->limit(3)
            ->pluck('category');

        foreach ($categories as $category) {
            $suggestions[] = [
                'type' => 'category',
                'value' => $category,
                'label' => "Categoría: {$category}",
            ];
        }

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Get popular items.
     */
    public function getPopularItems(int $limit = 10): array
    {
        $items = Item::where('is_active', true)
            ->where('is_available', true)
            ->orderByDesc('order_frequency')
            ->limit($limit)
            ->get();

        // Transform each item to ItemSearchData manually
        return $items->map(function ($item) {
            return ItemSearchData::from([
                'id' => $item->id,
                'name' => $item->name,
                'basePrice' => $item->base_price,
                'category' => $item->category,
                'description' => $item->description,
                'sku' => $item->sku,
                'isAvailable' => $item->is_available,
                'isActive' => $item->is_active,
                'preparationTime' => $item->preparation_time,
                'stockQuantity' => $item->stock_quantity,
                'image' => $item->image,
                'isPopular' => true,
                'orderFrequency' => $item->order_frequency ?? 0,
            ]);
        })->toArray();
    }

    /**
     * Get popular items as collection.
     */
    private function getPopularItemsData(int $limit = 20)
    {
        $items = Item::where('is_active', true)
            ->where('is_available', true)
            ->orderByDesc('order_frequency')
            ->limit($limit)
            ->get();

        return $items->map(function ($item) {
            return ItemSearchData::from([
                'id' => $item->id,
                'name' => $item->name,
                'basePrice' => $item->base_price,
                'category' => $item->category,
                'description' => $item->description,
                'sku' => $item->sku,
                'isAvailable' => $item->is_available,
                'isActive' => $item->is_active,
                'preparationTime' => $item->preparation_time,
                'stockQuantity' => $item->stock_quantity,
                'image' => $item->image,
                'isPopular' => true,
                'orderFrequency' => $item->order_frequency ?? 0,
            ]);
        });
    }

    /**
     * Get searchable fields configuration.
     */
    public function getSearchableFields(): array
    {
        return [
            'name' => ['weight' => 10],
            'sku' => ['weight' => 8, 'exact_match' => true],
            'description' => ['weight' => 5],
            'category' => ['weight' => 6],
            'search_keywords' => ['weight' => 7],
        ];
    }

    /**
     * Get filterable fields configuration.
     */
    public function getFilterableFields(): array
    {
        return [
            'category' => ['type' => 'string'],
            'is_available' => ['type' => 'boolean'],
            'is_active' => ['type' => 'boolean'],
            'base_price' => ['type' => 'range'],
            'preparation_time' => ['type' => 'range'],
            'stock_quantity' => ['type' => 'range'],
        ];
    }

    /**
     * Get sortable fields configuration.
     */
    public function getSortableFields(): array
    {
        return [
            'name' => 'Name',
            'base_price' => 'Price',
            'order_frequency' => 'Popularity',
            'created_at' => 'Date Added',
            'stock_quantity' => 'Stock',
        ];
    }

    /**
     * Record item selection from search.
     */
    public function recordSelection(string $searchId, mixed $entityId): void
    {
        // Record the selection
        DB::table('item_search_history')->insert([
            'search_id' => $searchId,
            'item_id' => $entityId,
            'user_id' => auth()->user()?->id,
            'created_at' => now(),
        ]);

        // Update item order frequency
        Item::where('id', $entityId)->increment('order_frequency');
    }

    /**
     * Apply filters to Scout builder.
     */
    private function applyFilters(Builder $builder, array $filters): Builder
    {
        if (!empty($filters['category'])) {
            $builder->where('category', $filters['category']);
        }

        if (isset($filters['is_available'])) {
            $builder->where('is_available', $filters['is_available']);
        }

        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['min_price'])) {
            $builder->where('base_price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $builder->where('base_price', '<=', $filters['max_price']);
        }

        if (!empty($filters['in_stock'])) {
            $builder->where('stock_quantity', '>', 0);
        }

        return $builder;
    }

    /**
     * Get facets for filtering.
     */
    private function getFacets(): array
    {
        return [
            'categories' => $this->getCategoryFacets(),
            'price_ranges' => $this->getPriceRangeFacets(),
            'availability' => $this->getAvailabilityFacets(),
        ];
    }

    /**
     * Get category facets.
     */
    private function getCategoryFacets(): array
    {
        return DB::table('items')
            ->select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->where('is_active', true)
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    /**
     * Get price range facets.
     */
    private function getPriceRangeFacets(): array
    {
        return [
            'under_2000' => Item::where('base_price', '<', 2000)->where('is_active', true)->count(),
            '2000_5000' => Item::whereBetween('base_price', [2000, 5000])->where('is_active', true)->count(),
            '5000_10000' => Item::whereBetween('base_price', [5000, 10000])->where('is_active', true)->count(),
            'over_10000' => Item::where('base_price', '>', 10000)->where('is_active', true)->count(),
        ];
    }

    /**
     * Get availability facets.
     */
    private function getAvailabilityFacets(): array
    {
        return [
            'available' => Item::where('is_available', true)->where('is_active', true)->count(),
            'unavailable' => Item::where('is_available', false)->where('is_active', true)->count(),
            'out_of_stock' => Item::where('stock_quantity', 0)->where('is_active', true)->count(),
        ];
    }

    /**
     * Calculate relevance score for an item.
     */
    private function calculateRelevanceScore(Item $item, string $query): float
    {
        $score = 0;
        $query = strtolower($query);

        // Exact name match
        if (strtolower($item->name) === $query) {
            $score += 100;
        } elseif (str_contains(strtolower($item->name), $query)) {
            $score += 50;
        }

        // SKU match
        if ($item->sku && str_contains(strtolower($item->sku), $query)) {
            $score += 40;
        }

        // Category match
        if ($item->category && str_contains(strtolower($item->category), $query)) {
            $score += 20;
        }

        // Popular items get a boost
        if ($item->order_frequency > 100) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Determine why an item matched the search.
     */
    private function determineMatchReason(Item $item, string $query): string
    {
        $query = strtolower($query);

        if (strtolower($item->name) === $query) {
            return 'exact';
        }

        if (str_contains(strtolower($item->name), $query)) {
            return 'fuzzy';
        }

        if ($item->category && str_contains(strtolower($item->category), $query)) {
            return 'category';
        }

        if ($item->order_frequency > 100) {
            return 'popular';
        }

        return 'content';
    }
}
