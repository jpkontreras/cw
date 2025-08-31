<?php

namespace App\Core\Data;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class SearchResultData extends Data
{
    public function __construct(
        public DataCollection $items,
        public string $query,
        public string $searchId,
        public int $total,
        public array $facets = [],
        public array $suggestions = [],
        public float $searchTime = 0,
    ) {}
    
    #[Computed]
    public function hasResults(): bool
    {
        return $this->total > 0;
    }
    
    #[Computed]
    public function isEmpty(): bool
    {
        return $this->total === 0;
    }
}