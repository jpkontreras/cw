<?php

namespace Colame\AiDiscovery\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiResponseCacheService
{
    /**
     * Cache key prefix for AI responses
     */
    private string $cachePrefix = 'ai_discovery:response:';

    /**
     * Cache key prefix for session data
     */
    private string $sessionPrefix = 'ai_discovery:session:';

    /**
     * Default TTL in seconds (24 hours)
     */
    private int $defaultTtl = 86400;

    /**
     * Generate a deterministic cache key from request parameters
     */
    public function generateCacheKey(array $params): string
    {
        // Normalize parameters for consistent cache keys
        $normalized = [
            'item_name' => $this->normalizeString($params['item_name'] ?? ''),
            'description' => $this->normalizeString($params['description'] ?? ''),
            'cuisine_type' => $params['cuisine_type'] ?? 'general',
            'location' => $params['location'] ?? 'Chile',
            'price_tier' => $params['price_tier'] ?? 'medium',
            'language' => $params['language'] ?? 'en',
        ];

        // Sort to ensure consistent key generation
        ksort($normalized);

        // Generate hash for the key
        $hash = md5(json_encode($normalized));

        return $this->cachePrefix . $hash;
    }

    /**
     * Generate cache key for a specific message in a session
     */
    public function generateMessageCacheKey(string $sessionId, string $userMessage, ?array $selections = null): string
    {
        $normalized = [
            'session' => $sessionId,
            'message' => $this->normalizeString($userMessage),
            'selections' => $selections ? json_encode($selections) : null,
        ];

        $hash = md5(json_encode($normalized));

        return $this->sessionPrefix . $sessionId . ':message:' . $hash;
    }

    /**
     * Get cached initial response for exact parameters match
     */
    public function getCachedInitialResponse(string $key): ?array
    {
        try {
            $cached = Cache::get($key);

            if ($cached) {
                Log::info('AI Discovery cache hit', [
                    'key' => $key,
                    'timestamp' => $cached['timestamp'] ?? null
                ]);

                // Update hit counter
                $this->incrementHitCounter($key);

                return $cached;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve from cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get cached message response
     */
    public function getCachedMessageResponse(string $key): ?array
    {
        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve message from cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache the initial AI response
     */
    public function cacheInitialResponse(string $key, array $response, ?int $ttl = null): void
    {
        try {
            $data = [
                'response' => $response,
                'timestamp' => now()->toIso8601String(),
                'ttl' => $ttl ?? $this->defaultTtl,
                'hits' => 0,
            ];

            Cache::put($key, $data, $ttl ?? $this->defaultTtl);

            Log::info('AI Discovery response cached', [
                'key' => $key,
                'ttl' => $ttl ?? $this->defaultTtl
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cache response', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache a message response in a session
     */
    public function cacheMessageResponse(string $key, array $response, ?int $ttl = null): void
    {
        try {
            Cache::put($key, $response, $ttl ?? $this->defaultTtl);
        } catch (\Exception $e) {
            Log::error('Failed to cache message response', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Store session data for resumption
     */
    public function storeSession(string $sessionId, array $sessionData, ?int $ttl = null): void
    {
        $key = $this->sessionPrefix . $sessionId;

        try {
            Cache::put($key, $sessionData, $ttl ?? $this->defaultTtl * 7); // 7 days for sessions

            Log::info('AI Discovery session stored', [
                'session_id' => $sessionId,
                'ttl' => $ttl ?? $this->defaultTtl * 7
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Retrieve session data
     */
    public function getSession(string $sessionId): ?array
    {
        $key = $this->sessionPrefix . $sessionId;

        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Clear cache for specific parameters
     */
    public function clearCache(array $params): bool
    {
        $key = $this->generateCacheKey($params);

        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error('Failed to clear cache', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all AI Discovery caches
     */
    public function clearAllCaches(): void
    {
        try {
            // Note: This requires Redis/Valkey to support pattern deletion
            // In production, you might want to track keys separately
            Cache::flush(); // This will flush ALL cache, be careful!

            Log::info('All AI Discovery caches cleared');
        } catch (\Exception $e) {
            Log::error('Failed to clear all caches', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This is a placeholder - in production you might want to track these metrics
        return [
            'total_keys' => 'N/A',
            'memory_usage' => 'N/A',
            'hit_rate' => 'N/A',
        ];
    }

    /**
     * Normalize string for consistent cache keys
     */
    private function normalizeString(string $str): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $str)));
    }

    /**
     * Increment hit counter for analytics
     */
    private function incrementHitCounter(string $key): void
    {
        try {
            $statsKey = $key . ':stats';
            $stats = Cache::get($statsKey, ['hits' => 0, 'last_hit' => null]);
            $stats['hits']++;
            $stats['last_hit'] = now()->toIso8601String();

            Cache::put($statsKey, $stats, $this->defaultTtl * 30); // Keep stats for 30 days
        } catch (\Exception $e) {
            // Don't fail on stats update
            Log::warning('Failed to update cache hit stats', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if we should use cache based on business rules
     */
    public function shouldUseCache(array $params): bool
    {
        // You can add business logic here
        // For example, don't cache if user explicitly requests fresh data
        if (isset($params['force_refresh']) && $params['force_refresh']) {
            return false;
        }

        // Always use cache for identical requests
        return true;
    }

    /**
     * Get TTL based on item type or other factors
     */
    public function getTtlForItem(string $itemName, string $priceTier = 'medium'): int
    {
        // Premium tier might get longer cache
        if ($priceTier === 'premium' || $priceTier === 'high') {
            return $this->defaultTtl * 2; // 48 hours
        }

        // Default TTL
        return $this->defaultTtl;
    }
}