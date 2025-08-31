<?php

namespace Colame\Order\Contracts;

use App\Core\Data\SearchResultData;

interface OrderSearchInterface
{
    /**
     * Search orders with filters.
     */
    public function search(string $query, array $filters = []): SearchResultData;
    
    /**
     * Search orders by date range.
     */
    public function searchByDateRange(\DateTime $from, \DateTime $to, array $filters = []): SearchResultData;
    
    /**
     * Get order suggestions based on partial input.
     */
    public function getSuggestions(string $query, int $limit = 5): array;
    
    /**
     * Record order selection from search.
     */
    public function recordSelection(string $searchId, int $orderId): void;
}