<?php

namespace App\Http\Controllers\Api;

use App\Core\Services\UnifiedSearchService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private UnifiedSearchService $searchService
    ) {}
    
    /**
     * Global search across all registered modules.
     * 
     * @api {get} /api/search Global Search
     * @apiParam {String} q Search query
     * @apiParam {String[]} [types] Types to search (e.g., ['orders', 'items'])
     * @apiParam {Object} [filters] Type-specific filters
     */
    public function global(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (empty($query)) {
            return response()->json([
                'error' => 'Search query is required',
            ], 400);
        }
        
        $types = $request->input('types', []);
        $options = [
            'filters' => $request->input('filters', []),
            'per_page' => $request->input('per_page', 20),
        ];
        
        $results = $this->searchService->searchAll($query, $types, $options);
        
        return response()->json([
            'query' => $query,
            'results' => $results,
            'meta' => [
                'total' => $this->calculateTotal($results),
                'types' => array_keys($results),
            ],
        ]);
    }
    
    /**
     * Type-specific search with advanced options.
     * 
     * @api {get} /api/search/{type} Type-specific Search
     * @apiParam {String} type Search type (e.g., 'orders', 'items')
     * @apiParam {String} q Search query
     * @apiParam {Object} [filters] Type-specific filters
     */
    public function searchType(Request $request, string $type): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (empty($query) && !$request->has('filters')) {
            return response()->json([
                'error' => 'Search query or filters are required',
            ], 400);
        }
        
        $handler = $this->searchService->getHandler($type);
        
        if (!$handler) {
            return response()->json([
                'error' => "Unknown search type: {$type}",
            ], 404);
        }
        
        $filters = $request->input('filters', []);
        $filters['per_page'] = $request->input('per_page', 20);
        
        $results = $handler->search($query, $filters);
        
        return response()->json($results);
    }
    
    /**
     * Get search suggestions/autocomplete.
     * 
     * @api {get} /api/search/suggest Search Suggestions
     * @apiParam {String} q Partial search query
     * @apiParam {String} [type] Type to get suggestions for
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $type = $request->input('type');
        
        if (empty($query)) {
            return response()->json([
                'suggestions' => [],
            ]);
        }
        
        $suggestions = [];
        
        if ($type) {
            $handler = $this->searchService->getHandler($type);
            if ($handler && method_exists($handler, 'getSuggestions')) {
                $suggestions = $handler->getSuggestions($query, 10);
            }
        } else {
            // Get suggestions from all registered modules
            foreach (['orders', 'items'] as $moduleType) {
                $handler = $this->searchService->getHandler($moduleType);
                if ($handler && method_exists($handler, 'getSuggestions')) {
                    $moduleSuggestions = $handler->getSuggestions($query, 5);
                    foreach ($moduleSuggestions as $suggestion) {
                        $suggestion['module'] = $moduleType;
                        $suggestions[] = $suggestion;
                    }
                }
            }
        }
        
        return response()->json([
            'query' => $query,
            'suggestions' => $suggestions,
        ]);
    }
    
    /**
     * Record a search selection for learning.
     * 
     * @api {post} /api/search/select Record Selection
     * @apiParam {String} search_id Search session ID
     * @apiParam {String} type Entity type
     * @apiParam {String} entity_id Selected entity ID
     */
    public function recordSelection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search_id' => 'required|string',
            'type' => 'required|string',
            'entity_id' => 'required',
        ]);
        
        $this->searchService->recordSelection(
            $validated['search_id'],
            $validated['type'],
            $validated['entity_id']
        );
        
        return response()->json([
            'success' => true,
        ]);
    }
    
    /**
     * Get popular searches.
     * 
     * @api {get} /api/search/popular Popular Searches
     * @apiParam {String} [type] Type to get popular searches for
     */
    public function popular(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $limit = $request->input('limit', 10);
        
        $popular = $this->searchService->getPopularSearches($type, $limit);
        
        return response()->json([
            'type' => $type,
            'popular' => $popular,
        ]);
    }
    
    /**
     * Calculate total results across all types.
     */
    private function calculateTotal(array $results): int
    {
        $total = 0;
        foreach ($results as $typeResults) {
            if (isset($typeResults->total)) {
                $total += $typeResults->total;
            }
        }
        return $total;
    }
}