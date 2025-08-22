<?php

declare(strict_types=1);

namespace Colame\Settings\Repositories;

use Colame\Settings\Contracts\SettingRepositoryInterface;
use Colame\Settings\Data\SettingData;
use Colame\Settings\Data\SettingGroupData;
use Colame\Settings\Enums\SettingCategory;
use Colame\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\DataCollection;

class SettingRepository implements SettingRepositoryInterface
{
    private const CACHE_PREFIX = 'settings:';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting by key
     */
    public function get(string $key): ?SettingData
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? SettingData::fromModel($setting) : null;
        });
    }

    /**
     * Get multiple settings by keys
     */
    public function getMany(array $keys): DataCollection
    {
        if (empty($keys)) {
            return SettingData::collection([]);
        }

        $settings = Setting::whereIn('key', $keys)->get();
        return SettingData::collect($settings, DataCollection::class);
    }

    /**
     * Get all settings
     */
    public function all(): DataCollection
    {
        $settings = Setting::ordered()->get();
        return SettingData::collect($settings, DataCollection::class);
    }

    /**
     * Get settings by category
     */
    public function byCategory(SettingCategory $category): DataCollection
    {
        $settings = Setting::byCategory($category)->ordered()->get();
        return SettingData::collect($settings, DataCollection::class);
    }

    /**
     * Get settings grouped by category
     */
    public function grouped(): DataCollection
    {
        $settings = Setting::ordered()->get();
        $grouped = $settings->groupBy('category');
        
        $groups = [];
        foreach (SettingCategory::cases() as $category) {
            $categorySettings = $grouped->get($category->value, collect());
            // Include all categories, even if empty, to show in the UI
            $groups[] = SettingGroupData::fromCategoryAndSettings(
                $category,
                SettingData::collect($categorySettings, DataCollection::class)
            );
        }
        
        return SettingGroupData::collect($groups, DataCollection::class);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value): SettingData
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            throw new \InvalidArgumentException("Setting with key '{$key}' does not exist");
        }
        
        $setting->value = $value;
        $setting->save();
        
        // Clear cache
        $this->clearCache($key);
        
        return SettingData::fromModel($setting);
    }

    /**
     * Set multiple settings
     */
    public function setMany(array $settings): DataCollection
    {
        $updated = [];
        
        foreach ($settings as $key => $value) {
            $updated[] = $this->set($key, $value);
        }
        
        return SettingData::collect($updated, DataCollection::class);
    }

    /**
     * Create or update a setting
     */
    public function upsert(array $data): SettingData
    {
        $setting = Setting::updateOrCreate(
            ['key' => $data['key']],
            $data
        );
        
        // Clear cache
        $this->clearCache($data['key']);
        
        return SettingData::fromModel($setting);
    }

    /**
     * Delete a setting
     */
    public function delete(string $key): bool
    {
        $result = Setting::where('key', $key)->delete() > 0;
        
        if ($result) {
            $this->clearCache($key);
        }
        
        return $result;
    }

    /**
     * Reset settings to defaults
     */
    public function resetToDefaults(?SettingCategory $category = null): void
    {
        $query = Setting::query();
        
        if ($category !== null) {
            $query->byCategory($category);
        }
        
        $query->update(['value' => null]);
        
        // Clear all cache
        $this->clearAllCache();
    }

    /**
     * Get setting value with type casting
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->get($key);
        
        if (!$setting) {
            return $default;
        }
        
        return $setting->getTypedValue() ?? $default;
    }

    /**
     * Check if a setting exists
     */
    public function exists(string $key): bool
    {
        return Setting::where('key', $key)->exists();
    }

    /**
     * Get settings that are required but not set
     */
    public function getMissingRequired(): DataCollection
    {
        $settings = Setting::required()
            ->whereNull('value')
            ->ordered()
            ->get();
            
        return SettingData::collect($settings, DataCollection::class);
    }

    /**
     * Export settings as array
     */
    public function export(?SettingCategory $category = null): array
    {
        $query = Setting::query();
        
        if ($category !== null) {
            $query->byCategory($category);
        }
        
        $settings = $query->ordered()->get();
        $export = [];
        
        foreach ($settings as $setting) {
            if (!$setting->is_encrypted && $setting->value !== null) {
                $export[$setting->key] = $setting->getTypedValue();
            }
        }
        
        return $export;
    }

    /**
     * Import settings from array
     */
    public function import(array $settings): DataCollection
    {
        $imported = [];
        
        foreach ($settings as $key => $value) {
            if ($this->exists($key)) {
                $imported[] = $this->set($key, $value);
            }
        }
        
        return SettingData::collect($imported, DataCollection::class);
    }

    /**
     * Clear cache for a specific key
     */
    private function clearCache(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Clear all settings cache
     */
    private function clearAllCache(): void
    {
        // Clear all cached settings
        $settings = Setting::pluck('key');
        foreach ($settings as $key) {
            $this->clearCache($key);
        }
    }
}