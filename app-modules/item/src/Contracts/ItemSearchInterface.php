<?php

namespace Colame\Item\Contracts;

use App\Core\Data\SearchResultData;

interface ItemSearchInterface
{
    /**
     * Search items with filters.
     */
    public function search(string $query, array $filters = []): SearchResultData;
    
    /**
     * Get item suggestions based on partial input.
     */
    public function getSuggestions(string $query, int $limit = 5): array;
    
    /**
     * Get popular items.
     */
    public function getPopularItems(int $limit = 10): array;
    
    /**
     * Record item selection from search.
     */
    public function recordSelection(string $searchId, int $itemId): void;
}