<?php

namespace Colame\AiDiscovery\Contracts;

use Colame\AiDiscovery\Data\SimilarityMatchData;
use Spatie\LaravelData\DataCollection;

interface SimilarityCacheInterface
{
    /**
     * Find similar items in cache based on name
     */
    public function findSimilar(
        string $itemName,
        float $threshold = 80.0,
        ?string $category = null
    ): DataCollection;

    /**
     * Calculate similarity between two items
     */
    public function calculateSimilarity(
        string $item1,
        string $item2,
        ?array $context = null
    ): float;

    /**
     * Store extraction results in cache
     */
    public function storeExtraction(
        array $itemData,
        array $extractedData,
        ?array $metadata = null
    ): void;

    /**
     * Consolidate patterns from multiple sources
     */
    public function consolidatePatterns(
        ?string $region = null,
        ?string $cuisineType = null
    ): array;

    /**
     * Get most used patterns for a region
     */
    public function getRegionalPatterns(string $region): array;

    /**
     * Update cache hit count
     */
    public function recordCacheHit(string $itemFingerprint): void;

    /**
     * Generate embedding vector for an item
     */
    public function generateEmbedding(
        string $itemName,
        ?string $description = null
    ): array;

    /**
     * Search by embedding similarity
     */
    public function searchByEmbedding(
        array $embedding,
        float $threshold = 0.8,
        int $limit = 10
    ): DataCollection;

    /**
     * Normalize item name for matching
     */
    public function normalizeItemName(string $itemName): string;

    /**
     * Generate fingerprint for item
     */
    public function generateFingerprint(
        string $itemName,
        ?string $category = null
    ): string;

    /**
     * Clean expired cache entries
     */
    public function cleanExpiredEntries(): int;

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array;

    /**
     * Preload cache for common items
     */
    public function preloadCommonItems(array $items): void;

    /**
     * Export cache data for backup
     */
    public function exportCache(?string $region = null): array;

    /**
     * Import cache data from backup
     */
    public function importCache(array $data): void;
}