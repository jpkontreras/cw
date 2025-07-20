<?php

declare(strict_types=1);

namespace App\Core\Contracts;

/**
 * Feature flag interface for module feature management
 */
interface FeatureFlagInterface
{
    /**
     * Check if a feature is enabled
     */
    public function isEnabled(string $feature, array $context = []): bool;

    /**
     * Check if a feature is enabled for a specific location
     */
    public function isEnabledForLocation(string $feature, int $locationId): bool;

    /**
     * Check if a feature is enabled for a specific user
     */
    public function isEnabledForUser(string $feature, int $userId): bool;

    /**
     * Get feature variant for A/B testing
     */
    public function getVariant(string $feature, array $context = []): ?string;

    /**
     * Enable a feature (for testing)
     */
    public function enable(string $feature): void;

    /**
     * Disable a feature (for testing)
     */
    public function disable(string $feature): void;

    /**
     * Get all enabled features
     */
    public function getEnabledFeatures(): array;

    /**
     * Check if a feature exists in configuration
     */
    public function featureExists(string $feature): bool;
}