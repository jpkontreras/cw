<?php

namespace Colame\Item\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Services\UnifiedSearchService;
use Colame\Item\Contracts\ItemSearchInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemSearchController extends Controller
{
    public function __construct(
        private UnifiedSearchService $unifiedSearch,
        private ItemSearchInterface $searchService // Keep for direct methods like getPopularItems
    ) {}
    
    /**
     * Search items via web route (for AJAX calls)
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $filters = $request->only(['category', 'min_price', 'max_price', 'is_available']);
        $filters['per_page'] = $request->input('per_page', 20);
        
        // Use UnifiedSearchService for searching - this provides logging and error handling
        $results = $this->unifiedSearch->searchType('items', $query, $filters);
        
        if (!$results) {
            return response()->json([
                'items' => [],
                'total' => 0,
                'query' => $query,
                'suggestions' => [],
                'facets' => [],
            ]);
        }
        
        // Transform for frontend consumption
        // DataCollection items might be arrays when mapped, so handle both cases
        $items = collect($results->items->toArray())->map(function ($item) {
            // $item is now an array representation of ItemSearchData
            if (is_array($item)) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['basePrice'],
                    'category' => $item['category'] ?? 'Uncategorized',
                    'description' => $item['description'] ?? null,
                    'preparationTime' => $item['preparationTime'] ?? null,
                    'isAvailable' => $item['isAvailable'] ?? true,
                    'isPopular' => $item['isPopular'] ?? false,
                    'orderFrequency' => $item['orderFrequency'] ?? 0,
                    'matchReason' => $item['matchReason'] ?? null,
                    'searchScore' => $item['searchScore'] ?? null,
                ];
            }
            // Fallback for object access (shouldn't happen with toArray())
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->basePrice,
                'category' => $item->category ?? 'Uncategorized',
                'description' => $item->description,
                'preparationTime' => $item->preparationTime,
                'isAvailable' => $item->isAvailable,
                'isPopular' => $item->isPopular ?? false,
                'orderFrequency' => $item->orderFrequency ?? 0,
                'matchReason' => $item->matchReason,
                'searchScore' => $item->searchScore,
            ];
        });
        
        return response()->json([
            'items' => $items,
            'total' => $results->total,
            'query' => $results->query,
            'suggestions' => $results->suggestions ?? [],
            'facets' => $results->facets ?? [],
        ]);
    }
    
    /**
     * Get item suggestions for autocomplete
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (empty($query)) {
            return response()->json(['suggestions' => []]);
        }
        
        $suggestions = $this->searchService->getSuggestions($query, 10);
        
        return response()->json([
            'query' => $query,
            'suggestions' => $suggestions,
        ]);
    }
    
    /**
     * Get popular items
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        
        $items = $this->searchService->getPopularItems($limit);
        
        // Items are already arrays from the service (toArray() was called)
        // Just return them directly with minor transformation for consistency
        $transformed = collect($items)->map(function ($item) {
            // $item is an array from ItemSearchData->toArray()
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['basePrice'],
                'category' => $item['category'] ?? 'Uncategorized',
                'preparationTime' => $item['preparationTime'] ?? 10,
                'orderFrequency' => $item['orderFrequency'] ?? 0,
                'isPopular' => true,
            ];
        });
        
        return response()->json([
            'items' => $transformed,
            'total' => count($transformed),
        ]);
    }
    
    /**
     * Record a selection for learning
     */
    public function recordSelection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search_id' => 'required|string',
            'item_id' => 'required|integer',
        ]);
        
        $this->searchService->recordSelection(
            $validated['search_id'],
            $validated['item_id']
        );
        
        return response()->json(['success' => true]);
    }
}