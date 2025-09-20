<?php

namespace Colame\Item\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Services\UnifiedSearchService;
use Colame\Item\Contracts\ItemSearchInterface;
use Colame\Item\Services\UserFavoritesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemSearchController extends Controller
{
    public function __construct(
        private UnifiedSearchService $unifiedSearch,
        private ItemSearchInterface $searchService, // Keep for direct methods like getPopularItems
        private UserFavoritesService $favoritesService
    ) {}
    
    /**
     * Search items via web route (for AJAX calls)
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $filters = $request->only(['category', 'min_price', 'max_price', 'is_available']);
        $filters['per_page'] = $request->input('per_page', 20);
        
        // Record search for authenticated users
        $searchId = null;
        if (Auth::check() && !empty($query)) {
            $searchId = $this->favoritesService->recordSearch(
                Auth::id(),
                $query,
                $request->session()->getId(),
                $request->ip()
            );
        }
        
        // Use UnifiedSearchService for searching - this provides logging and error handling
        $results = $this->unifiedSearch->searchType('items', $query, $filters);
        
        if (!$results) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total' => 0,
                    'query' => $query,
                    'suggestions' => [],
                    'facets' => [],
                ]
            ]);
        }
        
        // Get user favorites if authenticated
        $favoriteIds = [];
        if (Auth::check()) {
            $userFavorites = $this->favoritesService->getFavorites(Auth::id(), 100);
            $favoriteIds = $userFavorites->pluck('id')->toArray();
        }
        
        // Transform for frontend consumption
        // DataCollection items might be arrays when mapped, so handle both cases
        $items = collect($results->items->toArray())->map(function ($item) use ($favoriteIds) {
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
                    'isFavorite' => in_array($item['id'], $favoriteIds),
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
                'isFavorite' => in_array($item->id, $favoriteIds),
                'orderFrequency' => $item->orderFrequency ?? 0,
                'matchReason' => $item->matchReason,
                'searchScore' => $item->searchScore,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $results->total,
                'query' => $results->query,
                'searchId' => $searchId ?? $results->searchId,
                'suggestions' => $results->suggestions ?? [],
                'facets' => $results->facets ?? [],
            ]
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
        
        // Get user favorites if authenticated
        $favoriteIds = [];
        if (Auth::check()) {
            $userFavorites = $this->favoritesService->getFavorites(Auth::id(), 100);
            $favoriteIds = $userFavorites->pluck('id')->toArray();
        }
        
        // Items are already arrays from the service (toArray() was called)
        // Just return them directly with minor transformation for consistency
        $transformed = collect($items)->map(function ($item) use ($favoriteIds) {
            // $item is an array from ItemSearchData->toArray()
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['basePrice'],
                'category' => $item['category'] ?? 'Uncategorized',
                'preparationTime' => $item['preparationTime'] ?? 10,
                'orderFrequency' => $item['orderFrequency'] ?? 0,
                'isPopular' => true,
                'isFavorite' => in_array($item['id'], $favoriteIds),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $transformed,
                'total' => count($transformed),
            ]
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
        
        // Track item as recently added when selected
        if (Auth::check()) {
            $this->favoritesService->trackItemInteraction(
                Auth::id(),
                $validated['item_id'],
                'added'
            );
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Get user's favorite items
     */
    public function favorites(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['items' => []]);
        }
        
        $favorites = $this->favoritesService->getFavorites(Auth::id());
        
        // Transform favorites to match frontend expectations
        $transformed = $favorites->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->basePrice,
                'category' => $item->category ?? 'Uncategorized',
                'description' => $item->description,
                'preparationTime' => $item->preparationTime ?? 10,
                'isAvailable' => $item->isAvailable ?? true,
                'isFavorite' => true,
                'orderFrequency' => $item->orderFrequency ?? 0,
            ];
        });
        
        return response()->json([
            'items' => $transformed,
        ]);
    }
    
    /**
     * Toggle favorite status for an item
     */
    public function toggleFavorite(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
        ]);
        
        $result = $this->favoritesService->toggleFavorite(Auth::id(), $validated['item_id']);
        
        return response()->json($result);
    }
    
    /**
     * Get recent items for the user
     */
    public function recentItems(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['items' => []]);
        }
        
        $recentItems = $this->favoritesService->getRecentItems(Auth::id(), 10);
        
        // Get user favorites to mark them
        $favoriteIds = $this->favoritesService->getFavorites(Auth::id(), 100)->pluck('id')->toArray();
        
        // Transform recent items to match frontend expectations
        $transformed = $recentItems->map(function ($item) use ($favoriteIds) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->basePrice,
                'category' => $item->category ?? 'Uncategorized',
                'description' => $item->description,
                'preparationTime' => $item->preparationTime ?? 10,
                'isAvailable' => $item->isAvailable ?? true,
                'isFavorite' => in_array($item->id, $favoriteIds),
                'orderFrequency' => $item->orderFrequency ?? 0,
                'image' => $item->image,
            ];
        });
        
        return response()->json([
            'items' => $transformed,
        ]);
    }
    
    /**
     * Clear recent items for the user
     */
    public function clearRecentItems(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $cleared = $this->favoritesService->clearRecentItems(Auth::id());
        
        return response()->json([
            'success' => $cleared,
            'message' => 'Recent items cleared',
        ]);
    }
    
    /**
     * Get recent searches for the user (legacy - kept for compatibility)
     */
    public function recentSearches(): JsonResponse
    {
        // Redirect to recent items instead
        return $this->recentItems();
    }
    
    /**
     * Clear recent searches for the user (legacy - kept for compatibility)
     */
    public function clearRecentSearches(): JsonResponse
    {
        // Redirect to clear recent items instead
        return $this->clearRecentItems();
    }
}