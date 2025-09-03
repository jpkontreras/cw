<?php

namespace Colame\Item\Services;

use App\Core\Contracts\ModuleSearchInterface;
use App\Core\Data\SearchResultData;
use Colame\Item\Contracts\ItemSearchInterface;
use Colame\Item\Data\ItemSearchData;
use Colame\Item\Models\Item;
use Colame\Item\Services\UserFavoritesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Scout\Builder;
use Spatie\LaravelData\DataCollection;

class ItemSearchService implements ItemSearchInterface, ModuleSearchInterface
{
    protected UserFavoritesService $favoritesService;

    public function __construct(UserFavoritesService $favoritesService)
    {
        $this->favoritesService = $favoritesService;
    }
    public function search(string $query, array $filters = []): SearchResultData
    {
        $searchId = Str::uuid()->toString();
        $startTime = microtime(true);

        // Check for special keywords
        if ($query === 'recent:' || str_starts_with($query, 'recent:')) {
            // Get recent items for current user
            $items = $this->getRecentItemsData($filters['limit'] ?? 50);
        } elseif ($query === 'favorites:' || str_starts_with($query, 'favorites:')) {
            // Get favorite items for current user
            $items = $this->getFavoriteItemsData($filters['limit'] ?? 50);
        } elseif (empty($query)) {
            // If no query, return popular items
            $popularItems = $this->getPopularItemsData($filters['limit'] ?? 20);
            // Convert Collection to DataCollection
            $items = new DataCollection(ItemSearchData::class, $popularItems);
        } else {
            // Use Eloquent query with filters instead of Scout for better filter control
            $perPage = $filters['per_page'] ?? 20;
            $page = $filters['page'] ?? 1;
            
            // Build base query
            $itemQuery = Item::query()
                ->where('is_active', true)
                ->where('is_available', true);
            
            // Apply search to name and description
            if (!empty($query)) {
                $itemQuery->where(function($q) use ($query) {
                    $q->where('name', 'LIKE', '%' . $query . '%')
                      ->orWhere('description', 'LIKE', '%' . $query . '%')
                      ->orWhere('sku', 'LIKE', '%' . $query . '%')
                      ->orWhere('category', 'LIKE', '%' . $query . '%');
                });
            }
            
            // Apply filters
            if (!empty($filters['category'])) {
                $itemQuery->where('category', $filters['category']);
            }
            
            if (isset($filters['min_price']) && $filters['min_price'] !== null) {
                $itemQuery->where('base_price', '>=', $filters['min_price']);
            }
            
            if (isset($filters['max_price']) && $filters['max_price'] !== null) {
                $itemQuery->where('base_price', '<=', $filters['max_price']);
            }
            
            if (isset($filters['is_available']) && $filters['is_available']) {
                $itemQuery->where('is_available', true);
            }
            
            if (isset($filters['in_stock']) && $filters['in_stock']) {
                $itemQuery->where('stock_quantity', '>', 0);
            }
            
            // Order by relevance (items with query in name first, then by popularity)
            if (!empty($query)) {
                $itemQuery->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", ['%' . $query . '%'])
                         ->orderByDesc('order_frequency');
            } else {
                $itemQuery->orderByDesc('order_frequency');
            }
            
            // Paginate
            $results = $itemQuery->paginate($perPage, ['*'], 'page', $page);
            $paginatedItems = $results->items();

            $mappedItems = collect($paginatedItems)->map(function ($item) use ($query) {
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

            $items = new DataCollection(ItemSearchData::class, $mappedItems);
        }

        $facets = $this->getFacets();
        $suggestions = $this->getSuggestions($query);

        $searchTime = microtime(true) - $startTime;

        $displayQuery = $query;
        if (str_starts_with($query, 'recent:')) {
            $displayQuery = 'Recent Items';
        } elseif (str_starts_with($query, 'favorites:')) {
            $displayQuery = 'Favorite Items';
        }

        return new SearchResultData(
            items: $items,
            query: $displayQuery,
            searchId: $searchId,
            total: isset($results) ? $results->total() : $items->count(),
            facets: $facets,
            suggestions: $suggestions,
            searchTime: $searchTime
        );
    }

    public function getSuggestions(string $query, int $limit = 5): array
    {
        if (empty($query)) return [];

        $suggestions = [];

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

    public function getPopularItems(int $limit = 10): array
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
        })->toArray();
    }

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

    private function getRecentItemsData(int $limit = 50): DataCollection
    {
        $userId = auth()->user()?->id;

        if (!$userId) {
            return new DataCollection(ItemSearchData::class, []);
        }

        $recentItemIds = $this->favoritesService->getRecentItemIds($userId, $limit);

        if (empty($recentItemIds)) {
            return new DataCollection(ItemSearchData::class, []);
        }

        $items = Item::whereIn('id', $recentItemIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $mappedItems = collect($recentItemIds)
            ->map(function ($itemId) use ($items) {
                $item = $items->get($itemId);
                if (!$item) return null;

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
                    'isPopular' => ($item->order_frequency ?? 0) > 50,
                    'orderFrequency' => $item->order_frequency ?? 0,
                    'matchReason' => 'recent',
                ]);
            })
            ->filter();

        return new DataCollection(ItemSearchData::class, $mappedItems);
    }

    private function getFavoriteItemsData(int $limit = 50): DataCollection
    {
        $userId = auth()->user()?->id;

        if (!$userId) {
            return new DataCollection(ItemSearchData::class, []);
        }

        $favoriteItems = $this->favoritesService->getUserFavorites($userId);

        if ($favoriteItems->isEmpty()) {
            return new DataCollection(ItemSearchData::class, []);
        }

        $mappedItems = $favoriteItems->take($limit)->map(function ($item) {
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
                'isPopular' => ($item->order_frequency ?? 0) > 50,
                'orderFrequency' => $item->order_frequency ?? 0,
                'isFavorite' => true,
                'matchReason' => 'favorite',
            ]);
        });

        return new DataCollection(ItemSearchData::class, $mappedItems);
    }

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

    public function recordSelection(string $searchId, mixed $entityId): void
    {
        DB::table('item_search_history')->insert([
            'search_id' => $searchId,
            'item_id' => $entityId,
            'user_id' => auth()->user()?->id,
            'created_at' => now(),
        ]);

        Item::where('id', $entityId)->increment('order_frequency');
    }

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

        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $builder->where('base_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $builder->where('base_price', '<=', $filters['max_price']);
        }

        if (!empty($filters['in_stock'])) {
            $builder->where('stock_quantity', '>', 0);
        }

        return $builder;
    }

    private function getFacets(): array
    {
        return [
            'categories' => $this->getCategoryFacets(),
            'price_ranges' => $this->getPriceRangeFacets(),
            'availability' => $this->getAvailabilityFacets(),
        ];
    }

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

    private function getPriceRangeFacets(): array
    {
        return [
            'under_2000' => Item::where('base_price', '<', 2000)->where('is_active', true)->count(),
            '2000_5000' => Item::whereBetween('base_price', [2000, 5000])->where('is_active', true)->count(),
            '5000_10000' => Item::whereBetween('base_price', [5000, 10000])->where('is_active', true)->count(),
            'over_10000' => Item::where('base_price', '>', 10000)->where('is_active', true)->count(),
        ];
    }

    private function getAvailabilityFacets(): array
    {
        return [
            'available' => Item::where('is_available', true)->where('is_active', true)->count(),
            'unavailable' => Item::where('is_available', false)->where('is_active', true)->count(),
            'out_of_stock' => Item::where('stock_quantity', 0)->where('is_active', true)->count(),
        ];
    }

    private function calculateRelevanceScore(Item $item, string $query): float
    {
        $score = 0;
        $query = strtolower($query);

        if (strtolower($item->name) === $query) {
            $score += 100;
        } elseif (str_contains(strtolower($item->name), $query)) {
            $score += 50;
        }

        if ($item->sku && str_contains(strtolower($item->sku), $query)) {
            $score += 40;
        }

        if ($item->category && str_contains(strtolower($item->category), $query)) {
            $score += 20;
        }

        if ($item->order_frequency > 100) {
            $score += 10;
        }

        return $score;
    }

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
