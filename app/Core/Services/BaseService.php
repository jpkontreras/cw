<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Data\PaginatedResourceData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

/**
 * Base service class for all module services
 * 
 * Provides common functionality for data transformation,
 * feature flags, and logging.
 */
abstract class BaseService
{
    /**
     * Feature flag service
     */
    protected FeatureFlagInterface $features;

    public function __construct(FeatureFlagInterface $features)
    {
        $this->features = $features;
    }

    /**
     * Transform a model or array to a Data object
     * 
     * @template T of Data
     * @param mixed $data The data to transform
     * @param class-string<T> $dataClass The Data class to transform to
     * @return T|null
     */
    protected function transformToData(mixed $data, string $dataClass): ?Data
    {
        if ($data === null) {
            return null;
        }

        return $dataClass::from($data);
    }

    /**
     * Transform a collection to a DataCollection
     * 
     * @template T of Data
     * @param Collection|EloquentCollection|array $items
     * @param class-string<T> $dataClass
     * @return DataCollection<T>
     */
    protected function transformToDataCollection(
        Collection|EloquentCollection|array $items, 
        string $dataClass
    ): DataCollection {
        return $dataClass::collect($items, DataCollection::class);
    }

    /**
     * Create a lazy-loaded DataCollection
     * 
     * @template T of Data
     * @param \Closure $callback
     * @return Lazy
     */
    protected function lazyCollection(\Closure $callback): Lazy
    {
        return Lazy::create($callback);
    }

    /**
     * Create a lazy property that loads when a relation is loaded
     * 
     * @template T
     * @param string $relation
     * @param mixed $model
     * @param \Closure $callback
     * @return Lazy
     */
    protected function lazyWhenLoaded(string $relation, mixed $model, \Closure $callback): Lazy
    {
        return Lazy::whenLoaded($relation, $model, $callback);
    }

    /**
     * Transform a paginator to PaginatedResourceData
     * 
     * @template T of Data
     * @param LengthAwarePaginator $paginator
     * @param class-string<T> $dataClass
     * @param array $metadata Additional metadata
     * @return PaginatedResourceData
     */
    protected function transformPaginator(
        LengthAwarePaginator $paginator, 
        string $dataClass,
        array $metadata = []
    ): PaginatedResourceData {
        return PaginatedResourceData::fromPaginator($paginator, $dataClass, $metadata);
    }

    /**
     * Validate and create a Data object from array
     * 
     * @template T of Data
     * @param array $data
     * @param class-string<T> $dataClass
     * @return T
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateData(array $data, string $dataClass): Data
    {
        return $dataClass::validate($data);
    }

    /**
     * Try to create a Data object, returning null on failure
     * 
     * @template T of Data
     * @param mixed $data
     * @param class-string<T> $dataClass
     * @return T|null
     */
    protected function tryTransformToData(mixed $data, string $dataClass): ?Data
    {
        try {
            return $this->transformToData($data, $dataClass);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Log service action
     */
    protected function logAction(string $action, array $context = []): void
    {
        Log::info("Service action: {$action}", array_merge([
            'service' => static::class,
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Log service error
     */
    protected function logError(string $message, \Throwable $exception, array $context = []): void
    {
        Log::error("Service error: {$message}", array_merge([
            'service' => static::class,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Check if a feature is enabled
     */
    protected function isFeatureEnabled(string $feature, array $context = []): bool
    {
        return $this->features->isEnabled($feature, $context);
    }

    /**
     * Execute action with feature flag check
     */
    protected function withFeature(string $feature, callable $callback, array $context = [])
    {
        if (!$this->isFeatureEnabled($feature, $context)) {
            throw new \RuntimeException("Feature '{$feature}' is not enabled");
        }

        return $callback();
    }

    /**
     * Cache a computed property value
     * 
     * @param string $key
     * @param \Closure $callback
     * @param int $ttl Time to live in seconds
     * @return mixed
     */
    protected function cacheComputed(string $key, \Closure $callback, int $ttl = 3600): mixed
    {
        return cache()->remember(
            $this->computedCacheKey($key),
            $ttl,
            $callback
        );
    }

    /**
     * Generate a cache key for computed properties
     * 
     * @param string $key
     * @return string
     */
    private function computedCacheKey(string $key): string
    {
        return sprintf('computed:%s:%s', static::class, $key);
    }
}