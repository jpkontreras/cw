<?php

namespace App\Core\Contracts;

interface SearchableInterface
{
    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array;
    
    /**
     * Get the search index name for the model.
     */
    public function searchableAs(): string;
    
    /**
     * Get the value used to index the model.
     */
    public function getScoutKey(): mixed;
    
    /**
     * Get the key name used to index the model.
     */
    public function getScoutKeyName(): string;
}