<?php

declare(strict_types=1);

namespace Colame\Settings\Contracts;

use Colame\Settings\Data\SettingData;
use Colame\Settings\Enums\SettingCategory;

interface SettingCacheInterface
{
    /**
     * Get a cached setting
     */
    public function get(string $key): ?SettingData;

    /**
     * Set a cached setting
     */
    public function set(string $key, SettingData $setting): void;

    /**
     * Remove a cached setting
     */
    public function forget(string $key): void;

    /**
     * Clear cache for a category
     */
    public function clearCategory(SettingCategory $category): void;

    /**
     * Clear all cached settings
     */
    public function clear(): void;

    /**
     * Check if a setting is cached
     */
    public function has(string $key): bool;

    /**
     * Get all cached settings
     * 
     * @return array<string, SettingData>
     */
    public function all(): array;

    /**
     * Preload settings into cache
     * 
     * @param array<string> $keys
     */
    public function preload(array $keys): void;

    /**
     * Get cache key for a setting
     */
    public function getCacheKey(string $key): string;
}