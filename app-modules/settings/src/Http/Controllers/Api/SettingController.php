<?php

declare(strict_types=1);

namespace Colame\Settings\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Settings\Contracts\SettingServiceInterface;
use Colame\Settings\Data\BulkUpdateSettingData;
use Colame\Settings\Data\OrganizationSettingsData;
use Colame\Settings\Enums\SettingCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingServiceInterface $settingService
    ) {}

    /**
     * Get all settings grouped by category
     */
    public function index(): JsonResponse
    {
        $groups = $this->settingService->getAllGrouped();

        return response()->json([
            'data' => $groups->toArray(),
            'is_fully_configured' => $this->settingService->isFullyConfigured(),
        ]);
    }

    /**
     * Get settings by category
     */
    public function category(string $category): JsonResponse
    {
        try {
            $categoryEnum = SettingCategory::from($category);
            $settings = $this->settingService->getByCategory($categoryEnum);

            return response()->json([
                'data' => $settings->toArray(),
            ]);
        } catch (\ValueError $e) {
            return response()->json([
                'message' => 'Invalid category',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get a specific setting value
     */
    public function get(string $key): JsonResponse
    {
        $value = $this->settingService->get($key);

        if ($value === null) {
            return response()->json([
                'message' => 'Setting not found',
            ], 404);
        }

        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Update a specific setting
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => 'required',
        ]);

        try {
            $setting = $this->settingService->set($key, $request->get('value'));

            return response()->json([
                'message' => 'Setting updated successfully',
                'data' => $setting->toArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Failed to update setting',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $data = BulkUpdateSettingData::validateAndCreate($request);
        
        try {
            $updated = $this->settingService->bulkUpdate($data);

            return response()->json([
                'message' => 'Settings updated successfully',
                'data' => $updated->toArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get organization settings
     */
    public function organization(): JsonResponse
    {
        $settings = $this->settingService->getOrganizationSettings();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update organization settings
     */
    public function updateOrganization(Request $request): JsonResponse
    {
        $data = OrganizationSettingsData::validateAndCreate($request);
        $this->settingService->updateOrganizationSettings($data->toArray());

        return response()->json([
            'message' => 'Organization settings updated successfully',
            'data' => $data->toArray(),
        ]);
    }

    /**
     * Get order settings
     */
    public function orders(): JsonResponse
    {
        $settings = $this->settingService->getOrderSettings();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update order settings
     */
    public function updateOrders(Request $request): JsonResponse
    {
        $this->settingService->updateOrderSettings($request->all());

        return response()->json([
            'message' => 'Order settings updated successfully',
        ]);
    }

    /**
     * Get receipt settings
     */
    public function receipts(): JsonResponse
    {
        $settings = $this->settingService->getReceiptSettings();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update receipt settings
     */
    public function updateReceipts(Request $request): JsonResponse
    {
        $this->settingService->updateReceiptSettings($request->all());

        return response()->json([
            'message' => 'Receipt settings updated successfully',
        ]);
    }

    /**
     * Get tax settings
     */
    public function tax(): JsonResponse
    {
        $settings = $this->settingService->getTaxSettings();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update tax settings
     */
    public function updateTax(Request $request): JsonResponse
    {
        $this->settingService->updateTaxSettings($request->all());

        return response()->json([
            'message' => 'Tax settings updated successfully',
        ]);
    }

    /**
     * Get notification settings
     */
    public function notifications(): JsonResponse
    {
        $settings = $this->settingService->getNotificationSettings();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $this->settingService->updateNotificationSettings($request->all());

        return response()->json([
            'message' => 'Notification settings updated successfully',
        ]);
    }

    /**
     * Get integration settings
     */
    public function integrations(): JsonResponse
    {
        $settings = $this->settingService->getIntegrationSettings();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update integration settings
     */
    public function updateIntegrations(Request $request): JsonResponse
    {
        $this->settingService->updateIntegrationSettings($request->all());

        return response()->json([
            'message' => 'Integration settings updated successfully',
        ]);
    }

    /**
     * Validate settings
     */
    public function validateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        $result = $this->settingService->validate($request->get('settings'));

        return response()->json([
            'valid' => $result->isValid,
            'errors' => $result->errors,
            'warnings' => $result->warnings,
        ], $result->isValid ? 200 : 422);
    }

    /**
     * Export settings
     */
    public function export(Request $request): JsonResponse
    {
        $category = $request->has('category') 
            ? SettingCategory::from($request->get('category'))
            : null;

        $json = $this->settingService->exportToJson($category);

        return response()->json([
            'data' => json_decode($json, true),
        ]);
    }

    /**
     * Import settings
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        try {
            $imported = $this->settingService->importFromJson(
                json_encode($request->get('settings'))
            );

            return response()->json([
                'message' => 'Settings imported successfully',
                'data' => $imported->toArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset settings for a category
     */
    public function resetCategory(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|string',
        ]);

        try {
            $category = SettingCategory::from($request->get('category'));
            $this->settingService->resetCategory($category);

            return response()->json([
                'message' => "Settings for {$category->label()} have been reset to defaults",
            ]);
        } catch (\ValueError $e) {
            return response()->json([
                'message' => 'Invalid category',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset all settings
     */
    public function resetAll(): JsonResponse
    {
        $this->settingService->resetAll();

        return response()->json([
            'message' => 'All settings have been reset to defaults',
        ]);
    }

    /**
     * Initialize default settings
     */
    public function initialize(): JsonResponse
    {
        $this->settingService->initializeDefaults();

        return response()->json([
            'message' => 'Default settings initialized successfully',
        ]);
    }

    /**
     * Get missing required settings
     */
    public function missingRequired(): JsonResponse
    {
        $missing = $this->settingService->getMissingRequiredSettings();

        return response()->json([
            'data' => $missing->toArray(),
            'count' => $missing->count(),
        ]);
    }
}