<?php

declare(strict_types=1);

namespace Colame\Settings\Http\Controllers\Web;

use App\Core\Traits\HandlesPaginationBounds;
use App\Http\Controllers\Controller;
use Colame\Settings\Contracts\SettingServiceInterface;
use Colame\Settings\Data\BulkUpdateSettingData;
use Colame\Settings\Data\OrganizationSettingsData;
use Colame\Settings\Enums\SettingCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    use HandlesPaginationBounds;

    public function __construct(
        private readonly SettingServiceInterface $settingService
    ) {}

    /**
     * Display settings dashboard
     */
    public function index(): Response
    {
        try {
            $groups = $this->settingService->getAllGrouped();
            $isConfigured = $this->settingService->isFullyConfigured();
            $missingSettings = $this->settingService->getMissingRequiredSettings();
        } catch (\Exception $e) {
            // If there's an issue, provide empty data so the page still loads
            $groups = collect([]);
            $isConfigured = false;
            $missingSettings = collect([]);
        }

        return Inertia::render('settings/index', [
            'groups' => $groups instanceof \Spatie\LaravelData\DataCollection ? $groups->toArray() : [],
            'isFullyConfigured' => $isConfigured,
            'missingSettings' => $missingSettings instanceof \Spatie\LaravelData\DataCollection ? $missingSettings->toArray() : [],
        ]);
    }

    /**
     * Display organization settings
     */
    public function organization(): Response
    {
        $settings = $this->settingService->getByCategory(SettingCategory::ORGANIZATION);
        $localizationSettings = $this->settingService->getByCategory(SettingCategory::LOCALIZATION);
        
        $currentValues = $this->settingService->getOrganizationSettings();

        return Inertia::render('settings/organization', [
            'settings' => $settings->toArray(),
            'localizationSettings' => $localizationSettings->toArray(),
            'currentValues' => $currentValues,
        ]);
    }

    /**
     * Update organization settings
     */
    public function updateOrganization(Request $request): RedirectResponse
    {
        $data = OrganizationSettingsData::validateAndCreate($request);
        $this->settingService->updateOrganizationSettings($data->toArray());

        return redirect()
            ->route('system-settings.organization')
            ->with('success', 'Organization settings updated successfully');
    }

    /**
     * Display order settings
     */
    public function orders(): Response
    {
        $settings = $this->settingService->getByCategory(SettingCategory::ORDER);
        $currentValues = $this->settingService->getOrderSettings();

        return Inertia::render('settings/orders', [
            'settings' => $settings->toArray(),
            'currentValues' => $currentValues,
        ]);
    }

    /**
     * Update order settings
     */
    public function updateOrders(Request $request): RedirectResponse
    {
        $this->settingService->updateOrderSettings($request->all());

        return redirect()
            ->route('system-settings.orders')
            ->with('success', 'Order settings updated successfully');
    }

    /**
     * Display receipt settings
     */
    public function receipts(): Response
    {
        $settings = $this->settingService->getByCategory(SettingCategory::RECEIPT);
        $currentValues = $this->settingService->getReceiptSettings();

        return Inertia::render('settings/receipts', [
            'settings' => $settings->toArray(),
            'currentValues' => $currentValues,
        ]);
    }

    /**
     * Update receipt settings
     */
    public function updateReceipts(Request $request): RedirectResponse
    {
        $this->settingService->updateReceiptSettings($request->all());

        return redirect()
            ->route('system-settings.receipts')
            ->with('success', 'Receipt settings updated successfully');
    }

    /**
     * Display tax settings
     */
    public function tax(): Response
    {
        $settings = $this->settingService->getByCategory(SettingCategory::TAX);
        $currentValues = $this->settingService->getTaxSettings();

        return Inertia::render('settings/tax', [
            'settings' => $settings->toArray(),
            'currentValues' => $currentValues,
        ]);
    }

    /**
     * Update tax settings
     */
    public function updateTax(Request $request): RedirectResponse
    {
        $this->settingService->updateTaxSettings($request->all());

        return redirect()
            ->route('system-settings.tax')
            ->with('success', 'Tax settings updated successfully');
    }

    /**
     * Display notification settings
     */
    public function notifications(): Response
    {
        $settings = $this->settingService->getByCategory(SettingCategory::NOTIFICATION);
        $currentValues = $this->settingService->getNotificationSettings();

        return Inertia::render('settings/notifications', [
            'settings' => $settings->toArray(),
            'currentValues' => $currentValues,
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $this->settingService->updateNotificationSettings($request->all());

        return redirect()
            ->route('system-settings.notifications')
            ->with('success', 'Notification settings updated successfully');
    }

    /**
     * Display integration settings
     */
    public function integrations(): Response
    {
        $settings = $this->settingService->getByCategory(SettingCategory::INTEGRATION);
        $currentValues = $this->settingService->getIntegrationSettings();

        return Inertia::render('settings/integrations', [
            'settings' => $settings->toArray(),
            'currentValues' => $currentValues,
        ]);
    }

    /**
     * Update integration settings
     */
    public function updateIntegrations(Request $request): RedirectResponse
    {
        $this->settingService->updateIntegrationSettings($request->all());

        return redirect()
            ->route('system-settings.integrations')
            ->with('success', 'Integration settings updated successfully');
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = BulkUpdateSettingData::validateAndCreate($request);
        $this->settingService->bulkUpdate($data);

        return redirect()
            ->back()
            ->with('success', 'Settings updated successfully');
    }

    /**
     * Export settings
     */
    public function export(Request $request)
    {
        $category = $request->has('category') 
            ? SettingCategory::from($request->get('category'))
            : null;

        $json = $this->settingService->exportToJson($category);
        $filename = $category 
            ? "settings-{$category->value}-" . date('Y-m-d') . '.json'
            : 'settings-all-' . date('Y-m-d') . '.json';

        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Import settings form
     */
    public function importForm(): Response
    {
        return Inertia::render('settings/import');
    }

    /**
     * Import settings
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        $json = file_get_contents($request->file('file')->getRealPath());
        $this->settingService->importFromJson($json);

        return redirect()
            ->route('system-settings.index')
            ->with('success', 'Settings imported successfully');
    }

    /**
     * Reset settings for a category
     */
    public function resetCategory(Request $request): RedirectResponse
    {
        $request->validate([
            'category' => 'required|string',
        ]);

        $category = SettingCategory::from($request->get('category'));
        $this->settingService->resetCategory($category);

        return redirect()
            ->back()
            ->with('success', "Settings for {$category->label()} have been reset to defaults");
    }

    /**
     * Initialize default settings
     */
    public function initialize(): RedirectResponse
    {
        $this->settingService->initializeDefaults();

        return redirect()
            ->route('system-settings.index')
            ->with('success', 'Default settings initialized successfully');
    }
}