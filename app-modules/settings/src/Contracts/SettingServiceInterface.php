<?php

declare(strict_types=1);

namespace Colame\Settings\Contracts;

use Colame\Settings\Data\BulkUpdateSettingData;
use Colame\Settings\Data\SettingData;
use Colame\Settings\Data\SettingGroupData;
use Colame\Settings\Data\SettingValidationResultData;
use Colame\Settings\Enums\SettingCategory;
use Spatie\LaravelData\DataCollection;

interface SettingServiceInterface
{
    /**
     * Get a setting value
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value): SettingData;

    /**
     * Get all settings grouped by category
     * 
     * @return DataCollection<int, SettingGroupData>
     */
    public function getAllGrouped(): DataCollection;

    /**
     * Get settings for a specific category
     * 
     * @return DataCollection<int, SettingData>
     */
    public function getByCategory(SettingCategory $category): DataCollection;

    /**
     * Update multiple settings at once
     */
    public function bulkUpdate(BulkUpdateSettingData $data): DataCollection;

    /**
     * Validate setting values
     */
    public function validate(array $settings): SettingValidationResultData;

    /**
     * Reset settings to defaults for a category
     */
    public function resetCategory(SettingCategory $category): void;

    /**
     * Reset all settings to defaults
     */
    public function resetAll(): void;

    /**
     * Export settings to JSON
     */
    public function exportToJson(?SettingCategory $category = null): string;

    /**
     * Import settings from JSON
     */
    public function importFromJson(string $json): DataCollection;

    /**
     * Get organization settings
     */
    public function getOrganizationSettings(): array;

    /**
     * Update organization settings
     */
    public function updateOrganizationSettings(array $data): void;

    /**
     * Get order settings
     */
    public function getOrderSettings(): array;

    /**
     * Update order settings
     */
    public function updateOrderSettings(array $data): void;

    /**
     * Get receipt settings
     */
    public function getReceiptSettings(): array;

    /**
     * Update receipt settings
     */
    public function updateReceiptSettings(array $data): void;

    /**
     * Get tax settings
     */
    public function getTaxSettings(): array;

    /**
     * Update tax settings
     */
    public function updateTaxSettings(array $data): void;

    /**
     * Get notification settings
     */
    public function getNotificationSettings(): array;

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(array $data): void;

    /**
     * Get integration settings
     */
    public function getIntegrationSettings(): array;

    /**
     * Update integration settings
     */
    public function updateIntegrationSettings(array $data): void;

    /**
     * Check if all required settings are configured
     */
    public function isFullyConfigured(): bool;

    /**
     * Get missing required settings
     * 
     * @return DataCollection<int, SettingData>
     */
    public function getMissingRequiredSettings(): DataCollection;

    /**
     * Initialize default settings for new installation
     */
    public function initializeDefaults(): void;
}