<?php

declare(strict_types=1);

namespace Colame\Settings\Services;

use Colame\Settings\Contracts\SettingRepositoryInterface;
use Colame\Settings\Contracts\SettingServiceInterface;
use Colame\Settings\Data\BulkUpdateSettingData;
use Colame\Settings\Data\OrganizationSettingsData;
use Colame\Settings\Data\SettingData;
use Colame\Settings\Data\SettingGroupData;
use Colame\Settings\Data\SettingValidationResultData;
use Colame\Settings\Enums\SettingCategory;
use Colame\Settings\Enums\SettingType;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\DataCollection;

class SettingService implements SettingServiceInterface
{
    public function __construct(
        private readonly SettingRepositoryInterface $repository
    ) {}

    /**
     * Get a setting value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repository->getValue($key, $default);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value): SettingData
    {
        return $this->repository->set($key, $value);
    }

    /**
     * Get all settings grouped by category
     */
    public function getAllGrouped(): DataCollection
    {
        return $this->repository->grouped();
    }

    /**
     * Get settings for a specific category
     */
    public function getByCategory(SettingCategory $category): DataCollection
    {
        return $this->repository->byCategory($category);
    }

    /**
     * Update multiple settings at once
     */
    public function bulkUpdate(BulkUpdateSettingData $data): DataCollection
    {
        if ($data->validateBeforeUpdate) {
            $validation = $this->validate($data->settings);
            if (!$validation->isValid) {
                throw new \InvalidArgumentException('Settings validation failed: ' . json_encode($validation->errors));
            }
        }

        return $this->repository->setMany($data->settings);
    }

    /**
     * Validate setting values
     */
    public function validate(array $settings): SettingValidationResultData
    {
        $errors = [];
        $warnings = [];
        $validatedSettings = [];

        foreach ($settings as $key => $value) {
            $setting = $this->repository->get($key);
            
            if (!$setting) {
                $errors[$key][] = "Setting '{$key}' does not exist";
                continue;
            }

            // Type validation
            if (!$this->validateType($value, $setting->type)) {
                $errors[$key][] = "Invalid type for setting '{$key}'. Expected {$setting->type->value}";
                continue;
            }

            // Custom validation rules
            if ($setting->validation) {
                $validator = Validator::make(
                    [$key => $value],
                    [$key => $setting->validation]
                );

                if ($validator->fails()) {
                    $errors[$key] = $validator->errors()->get($key);
                    continue;
                }
            }

            // Check required fields
            if ($setting->isRequired && ($value === null || $value === '')) {
                $errors[$key][] = "Setting '{$key}' is required";
                continue;
            }

            // Check options for select fields
            if ($setting->type->requiresOptions() && $setting->options) {
                if (!in_array($value, $setting->options, true)) {
                    $errors[$key][] = "Invalid option for setting '{$key}'";
                    continue;
                }
            }

            $validatedSettings[$key] = $value;
        }

        if (empty($errors)) {
            return SettingValidationResultData::success($validatedSettings);
        }

        return SettingValidationResultData::failure($errors, $warnings);
    }

    /**
     * Reset settings to defaults for a category
     */
    public function resetCategory(SettingCategory $category): void
    {
        $this->repository->resetToDefaults($category);
    }

    /**
     * Reset all settings to defaults
     */
    public function resetAll(): void
    {
        $this->repository->resetToDefaults();
    }

    /**
     * Export settings to JSON
     */
    public function exportToJson(?SettingCategory $category = null): string
    {
        $settings = $this->repository->export($category);
        return json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * Import settings from JSON
     */
    public function importFromJson(string $json): DataCollection
    {
        $settings = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return $this->repository->import($settings);
    }

    /**
     * Get organization settings
     */
    public function getOrganizationSettings(): array
    {
        $orgSettings = $this->repository->byCategory(SettingCategory::ORGANIZATION);
        $locSettings = $this->repository->byCategory(SettingCategory::LOCALIZATION);
        
        $settings = [];
        foreach ($orgSettings as $setting) {
            $settings[$setting->key] = $setting->getTypedValue();
        }
        foreach ($locSettings as $setting) {
            $settings[$setting->key] = $setting->getTypedValue();
        }

        return $settings;
    }

    /**
     * Update organization settings
     */
    public function updateOrganizationSettings(array $data): void
    {
        $orgData = OrganizationSettingsData::validateAndCreate($data);
        $this->repository->setMany($orgData->toSettings());
    }

    /**
     * Get order settings
     */
    public function getOrderSettings(): array
    {
        $settings = $this->repository->byCategory(SettingCategory::ORDER);
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getTypedValue();
        }

        return $result;
    }

    /**
     * Update order settings
     */
    public function updateOrderSettings(array $data): void
    {
        $prefixed = [];
        foreach ($data as $key => $value) {
            $prefixed["order.{$key}"] = $value;
        }
        
        $this->repository->setMany($prefixed);
    }

    /**
     * Get receipt settings
     */
    public function getReceiptSettings(): array
    {
        $settings = $this->repository->byCategory(SettingCategory::RECEIPT);
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getTypedValue();
        }

        return $result;
    }

    /**
     * Update receipt settings
     */
    public function updateReceiptSettings(array $data): void
    {
        $prefixed = [];
        foreach ($data as $key => $value) {
            $prefixed["receipt.{$key}"] = $value;
        }
        
        $this->repository->setMany($prefixed);
    }

    /**
     * Get tax settings
     */
    public function getTaxSettings(): array
    {
        $settings = $this->repository->byCategory(SettingCategory::TAX);
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getTypedValue();
        }

        return $result;
    }

    /**
     * Update tax settings
     */
    public function updateTaxSettings(array $data): void
    {
        $prefixed = [];
        foreach ($data as $key => $value) {
            $prefixed["tax.{$key}"] = $value;
        }
        
        $this->repository->setMany($prefixed);
    }

    /**
     * Get notification settings
     */
    public function getNotificationSettings(): array
    {
        $settings = $this->repository->byCategory(SettingCategory::NOTIFICATION);
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getTypedValue();
        }

        return $result;
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(array $data): void
    {
        $prefixed = [];
        foreach ($data as $key => $value) {
            $prefixed["notification.{$key}"] = $value;
        }
        
        $this->repository->setMany($prefixed);
    }

    /**
     * Get integration settings
     */
    public function getIntegrationSettings(): array
    {
        $settings = $this->repository->byCategory(SettingCategory::INTEGRATION);
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getTypedValue();
        }

        return $result;
    }

    /**
     * Update integration settings
     */
    public function updateIntegrationSettings(array $data): void
    {
        $prefixed = [];
        foreach ($data as $key => $value) {
            $prefixed["integration.{$key}"] = $value;
        }
        
        $this->repository->setMany($prefixed);
    }

    /**
     * Check if all required settings are configured
     */
    public function isFullyConfigured(): bool
    {
        $missing = $this->repository->getMissingRequired();
        return $missing->count() === 0;
    }

    /**
     * Get missing required settings
     */
    public function getMissingRequiredSettings(): DataCollection
    {
        return $this->repository->getMissingRequired();
    }

    /**
     * Initialize default settings for new installation
     */
    public function initializeDefaults(): void
    {
        $this->seedDefaultSettings();
    }

    /**
     * Validate value type
     */
    private function validateType(mixed $value, SettingType $type): bool
    {
        if ($value === null) {
            return true; // Null is allowed for all types
        }

        return match ($type) {
            SettingType::STRING, SettingType::FILE, SettingType::COLOR, 
            SettingType::SELECT, SettingType::ENCRYPTED => is_string($value),
            SettingType::INTEGER => is_int($value) || (is_string($value) && ctype_digit($value)),
            SettingType::FLOAT => is_float($value) || is_int($value) || is_numeric($value),
            SettingType::BOOLEAN => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
            SettingType::JSON, SettingType::ARRAY, SettingType::MULTISELECT => is_array($value) || is_string($value),
            SettingType::DATE, SettingType::DATETIME, SettingType::TIME => is_string($value) || $value instanceof \DateTimeInterface,
        };
    }

    /**
     * Seed default settings
     */
    private function seedDefaultSettings(): void
    {
        $defaults = $this->getDefaultSettings();
        
        foreach ($defaults as $data) {
            $this->repository->upsert($data);
        }
    }

    /**
     * Get default settings configuration
     */
    private function getDefaultSettings(): array
    {
        return [
            // Organization settings
            [
                'key' => 'organization.business_name',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Business Name',
                'description' => 'The name of your business',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'organization.email',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Business Email',
                'description' => 'Primary email address for business communications',
                'is_required' => true,
                'validation' => ['email'],
                'sort_order' => 2,
            ],
            [
                'key' => 'organization.phone',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Business Phone',
                'description' => 'Primary phone number',
                'is_required' => true,
                'sort_order' => 3,
            ],
            
            // Tax settings
            [
                'key' => 'tax.enabled',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::TAX,
                'label' => 'Enable Tax',
                'description' => 'Enable tax calculations',
                'default_value' => '1',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'tax.rate',
                'type' => SettingType::FLOAT,
                'category' => SettingCategory::TAX,
                'label' => 'Tax Rate (%)',
                'description' => 'Default tax rate percentage',
                'default_value' => '19',
                'validation' => ['numeric', 'min:0', 'max:100'],
                'sort_order' => 2,
            ],
            
            // Order settings
            [
                'key' => 'order.number_format',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORDER,
                'label' => 'Order Number Format',
                'description' => 'Format for order numbers (use {number} for sequence)',
                'default_value' => 'ORD-{number}',
                'is_required' => true,
                'sort_order' => 1,
            ],
            
            // Localization settings
            [
                'key' => 'localization.currency',
                'type' => SettingType::STRING,
                'category' => SettingCategory::LOCALIZATION,
                'label' => 'Currency',
                'description' => 'Default currency code',
                'default_value' => 'CLP',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'localization.timezone',
                'type' => SettingType::STRING,
                'category' => SettingCategory::LOCALIZATION,
                'label' => 'Timezone',
                'description' => 'System timezone',
                'default_value' => 'America/Santiago',
                'is_required' => true,
                'sort_order' => 2,
            ],
            
            // Add more default settings as needed...
        ];
    }
}