<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Support\Facades\Log;

/**
 * Base service class for all module services
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
}