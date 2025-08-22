<?php

declare(strict_types=1);

namespace Colame\Settings\Contracts;

use Colame\Settings\Data\SettingData;
use Colame\Settings\Data\SettingGroupData;
use Colame\Settings\Enums\SettingCategory;
use Spatie\LaravelData\DataCollection;

interface SettingRepositoryInterface
{
    /**
     * Get a setting by key
     */
    public function get(string $key): ?SettingData;

    /**
     * Get multiple settings by keys
     * 
     * @param array<string> $keys
     * @return DataCollection<int, SettingData>
     */
    public function getMany(array $keys): DataCollection;

    /**
     * Get all settings
     * 
     * @return DataCollection<int, SettingData>
     */
    public function all(): DataCollection;

    /**
     * Get settings by category
     * 
     * @return DataCollection<int, SettingData>
     */
    public function byCategory(SettingCategory $category): DataCollection;

    /**
     * Get settings grouped by category
     * 
     * @return DataCollection<int, SettingGroupData>
     */
    public function grouped(): DataCollection;

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value): SettingData;

    /**
     * Set multiple settings
     * 
     * @param array<string, mixed> $settings
     * @return DataCollection<int, SettingData>
     */
    public function setMany(array $settings): DataCollection;

    /**
     * Create or update a setting
     */
    public function upsert(array $data): SettingData;

    /**
     * Delete a setting
     */
    public function delete(string $key): bool;

    /**
     * Reset settings to defaults
     * 
     * @param SettingCategory|null $category If null, reset all settings
     */
    public function resetToDefaults(?SettingCategory $category = null): void;

    /**
     * Get setting value with type casting
     */
    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * Check if a setting exists
     */
    public function exists(string $key): bool;

    /**
     * Get settings that are required but not set
     * 
     * @return DataCollection<int, SettingData>
     */
    public function getMissingRequired(): DataCollection;

    /**
     * Export settings as array
     * 
     * @param SettingCategory|null $category
     * @return array<string, mixed>
     */
    public function export(?SettingCategory $category = null): array;

    /**
     * Import settings from array
     * 
     * @param array<string, mixed> $settings
     * @return DataCollection<int, SettingData>
     */
    public function import(array $settings): DataCollection;
}