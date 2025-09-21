<?php

namespace Colame\AiDiscovery\Services;

use Colame\AiDiscovery\Contracts\SimilarityCacheInterface;
use Colame\AiDiscovery\Data\SimilarityMatchData;
use Colame\AiDiscovery\Data\ExtractedVariantData;
use Colame\AiDiscovery\Data\ExtractedModifierData;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SimilarityCacheService implements SimilarityCacheInterface
{
    private string $cachePrefix = 'ai_discovery:similarity:';
    private string $patternPrefix = 'ai_discovery:pattern:';
    private int $defaultTtl = 86400 * 30; // 30 days

    public function findSimilar(
        string $itemName,
        float $threshold = 80.0,
        ?string $category = null
    ): DataCollection {
        $normalizedName = $this->normalizeItemName($itemName);

        // Try to find cached similar items
        $cacheKey = $this->cachePrefix . 'similar:' . md5($normalizedName . $category);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return new DataCollection(SimilarityMatchData::class, $cached);
        }

        // For now, return empty collection since we don't have a database of items
        // This would be populated over time as items are discovered
        return new DataCollection(SimilarityMatchData::class, []);
    }

    public function calculateSimilarity(
        string $item1,
        string $item2,
        ?array $context = null
    ): float {
        $normalized1 = $this->normalizeItemName($item1);
        $normalized2 = $this->normalizeItemName($item2);

        // Simple similarity calculation
        $similarity = 0.0;

        // Exact match
        if ($normalized1 === $normalized2) {
            return 100.0;
        }

        // Levenshtein distance-based similarity
        $maxLen = max(strlen($normalized1), strlen($normalized2));
        if ($maxLen > 0) {
            $distance = levenshtein($normalized1, $normalized2);
            $similarity = (1 - $distance / $maxLen) * 100;
        }

        // Apply context boost if available
        if ($context && isset($context['category']) && isset($context['compareCategory'])) {
            if ($context['category'] === $context['compareCategory']) {
                $similarity = min(100, $similarity * 1.1); // 10% boost for same category
            }
        }

        return round($similarity, 2);
    }

    public function storeExtraction(
        array $itemData,
        array $extractedData,
        ?array $metadata = null
    ): void {
        $fingerprint = $this->generateFingerprint(
            $itemData['name'],
            $itemData['category'] ?? null
        );

        $cacheKey = $this->cachePrefix . 'extraction:' . $fingerprint;

        $data = [
            'item_fingerprint' => $fingerprint,
            'original_item_name' => $itemData['name'],
            'normalized_name' => $this->normalizeItemName($itemData['name']),
            'item_category' => $itemData['category'] ?? null,
            'extracted_data' => $extractedData,
            'metadata' => array_merge($metadata ?? [], [
                'region' => $itemData['region'] ?? null,
                'cuisine_type' => $itemData['cuisine_type'] ?? null,
                'price_tier' => $itemData['price_tier'] ?? null,
            ]),
            'confidence_score' => $extractedData['confidence'] ?? 0.85,
            'created_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $data, $this->defaultTtl);

        // Update index of all extractions
        $this->updateExtractionIndex($cacheKey, $itemData['name']);

        Log::info('Stored extraction in Redis', [
            'fingerprint' => $fingerprint,
            'item' => $itemData['name'],
            'ttl' => $this->defaultTtl
        ]);
    }

    public function consolidatePatterns(
        ?string $region = null,
        ?string $cuisineType = null
    ): array {
        // Get patterns from cache
        $patternKey = $this->patternPrefix . 'consolidated:' . md5($region . ':' . $cuisineType);

        return Cache::get($patternKey, [
            'variants' => [],
            'modifiers' => [],
            'pricing' => [],
        ]);
    }

    public function getRegionalPatterns(string $region): array
    {
        $cacheKey = $this->patternPrefix . 'regional:' . $region;

        return Cache::get($cacheKey, []);
    }

    public function recordCacheHit(string $itemFingerprint): void
    {
        $statsKey = $this->cachePrefix . 'stats:' . $itemFingerprint;
        $stats = Cache::get($statsKey, ['hits' => 0, 'last_hit' => null]);

        $stats['hits']++;
        $stats['last_hit'] = now()->toIso8601String();

        Cache::put($statsKey, $stats, $this->defaultTtl);
    }

    public function generateEmbedding(
        string $itemName,
        ?string $description = null
    ): array {
        // For now, return empty array
        // In production, this would call an embedding API
        return [];
    }

    public function searchByEmbedding(
        array $embedding,
        float $threshold = 0.8,
        int $limit = 10
    ): DataCollection {
        // For now, return empty collection
        // In production, this would search vector database
        return new DataCollection(SimilarityMatchData::class, []);
    }

    public function normalizeItemName(string $itemName): string
    {
        $name = mb_strtolower($itemName);
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    public function generateFingerprint(
        string $itemName,
        ?string $category = null
    ): string {
        $normalized = $this->normalizeItemName($itemName) . ':' . ($category ?? 'uncategorized');
        return md5($normalized);
    }

    public function cleanExpiredEntries(): int
    {
        // Redis handles expiration automatically
        // This method is kept for interface compatibility
        Log::info('Cache expiration handled automatically by Redis');
        return 0;
    }

    public function getCacheStatistics(): array
    {
        // Get basic stats from Redis
        $indexKey = $this->cachePrefix . 'index:all';
        $allItems = Cache::get($indexKey, []);

        return [
            'total_cached_items' => count($allItems),
            'total_learned_patterns' => 0, // Simplified - no longer tracking patterns separately
            'most_used_items' => [],
            'cache_hit_rate' => 0,
            'storage_size_mb' => 0, // Redis manages this
        ];
    }

    public function preloadCommonItems(array $items): void
    {
        foreach ($items as $item) {
            $cacheKey = $this->cachePrefix . 'preload:' . $this->normalizeItemName($item['name']);

            Cache::put($cacheKey, [
                'name' => $item['name'],
                'variants' => $item['variants'] ?? [],
                'modifiers' => $item['modifiers'] ?? [],
            ], $this->defaultTtl);
        }

        Log::info('Preloaded common items', ['count' => count($items)]);
    }

    public function exportCache(?string $region = null): array
    {
        $indexKey = $this->cachePrefix . 'index:all';
        $allItems = Cache::get($indexKey, []);

        $exports = [];
        foreach ($allItems as $itemKey => $itemName) {
            $data = Cache::get($itemKey);
            if ($data && (!$region || ($data['metadata']['region'] ?? null) === $region)) {
                $exports[] = $data;
            }
        }

        return $exports;
    }

    public function importCache(array $data): void
    {
        foreach ($data as $item) {
            if (isset($item['item_fingerprint'])) {
                $cacheKey = $this->cachePrefix . 'extraction:' . $item['item_fingerprint'];
                Cache::put($cacheKey, $item, $this->defaultTtl);

                // Update index
                $this->updateExtractionIndex($cacheKey, $item['original_item_name'] ?? 'Unknown');
            }
        }

        Log::info('Imported cache data', ['count' => count($data)]);
    }

    // Helper methods (kept private as they're not in the interface)

    private function updateExtractionIndex(string $cacheKey, string $itemName): void
    {
        $indexKey = $this->cachePrefix . 'index:all';
        $index = Cache::get($indexKey, []);

        $index[$cacheKey] = $itemName;

        Cache::put($indexKey, $index, $this->defaultTtl * 3); // Keep index longer
    }
}