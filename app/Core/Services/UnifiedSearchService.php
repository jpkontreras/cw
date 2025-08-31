<?php

namespace App\Core\Services;

use App\Core\Contracts\ModuleSearchInterface;
use App\Core\Data\SearchResultData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnifiedSearchService
{
    private array $searchModules = [];
    
    /**
     * Register a searchable module.
     */
    public function registerModule(string $type, ModuleSearchInterface $handler): void
    {
        $this->searchModules[$type] = $handler;
    }
    
    /**
     * Get a specific module handler.
     */
    public function getHandler(string $type): ?ModuleSearchInterface
    {
        return $this->searchModules[$type] ?? null;
    }
    
    /**
     * Search across all registered modules.
     */
    public function searchAll(string $query, array $types = [], array $options = []): array
    {
        $searchId = $this->recordGlobalSearch($query, $types);
        $results = [];
        
        // If no types specified, search all
        $searchTypes = empty($types) ? array_keys($this->searchModules) : $types;
        
        foreach ($searchTypes as $type) {
            if (isset($this->searchModules[$type])) {
                try {
                    $moduleResults = $this->searchModules[$type]->search(
                        $query, 
                        $options['filters'][$type] ?? []
                    );
                    
                    // Override searchId with global one
                    $moduleResults->searchId = $searchId;
                    $results[$type] = $moduleResults;
                } catch (\Exception $e) {
                    // Log error but continue with other modules
                    logger()->error("Search failed for module {$type}", [
                        'error' => $e->getMessage(),
                        'query' => $query
                    ]);
                    
                    // Return empty result for failed module
                    $results[$type] = new SearchResultData(
                        items: collect(),
                        query: $query,
                        searchId: $searchId,
                        total: 0
                    );
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Search a specific module type.
     */
    public function searchType(string $type, string $query, array $filters = []): ?SearchResultData
    {
        if (!isset($this->searchModules[$type])) {
            return null;
        }
        
        return $this->searchModules[$type]->search($query, $filters);
    }
    
    /**
     * Record a global search for analytics.
     */
    private function recordGlobalSearch(string $query, array $types): string
    {
        $searchId = (string) Str::uuid();
        
        DB::table('search_logs')->insert([
            'id' => $searchId,
            'query' => $query,
            'types' => json_encode($types),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
        
        return $searchId;
    }
    
    /**
     * Record a selection from search results.
     */
    public function recordSelection(string $searchId, string $type, mixed $entityId): void
    {
        // Record in general search selections
        DB::table('search_selections')->insert([
            'search_id' => $searchId,
            'entity_type' => $type,
            'entity_id' => $entityId,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
        
        // Let module record its own selection
        if (isset($this->searchModules[$type])) {
            $this->searchModules[$type]->recordSelection($searchId, $entityId);
        }
    }
    
    /**
     * Get popular searches across all types or specific type.
     */
    public function getPopularSearches(?string $type = null, int $limit = 10): array
    {
        $query = DB::table('search_logs')
            ->select('query', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>', now()->subDays(7))
            ->whereNotNull('query')
            ->where('query', '!=', '');
        
        if ($type) {
            $query->whereJsonContains('types', $type);
        }
        
        return $query->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'query')
            ->toArray();
    }
}