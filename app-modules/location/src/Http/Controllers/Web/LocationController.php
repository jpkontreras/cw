<?php

declare(strict_types=1);

namespace Colame\Location\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Location\Contracts\LocationServiceInterface;
use Colame\Location\Data\CreateLocationData;
use Colame\Location\Data\UpdateLocationData;
use Colame\Location\Data\UpdateLocationSettingsData;
use Colame\Location\Enums\LocationType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationServiceInterface $locationService
    ) {}

    /**
     * Display a listing of locations.
     */
    public function index(Request $request): Response
    {
        $locations = $this->locationService->getLocationHierarchy();
        
        return Inertia::render('location/index', [
            'locations' => $locations->toArray(),
            'canCreate' => true, // TODO: Add permission check
        ]);
    }

    /**
     * Show the form for creating a new location.
     */
    public function create(): Response
    {
        $parentLocations = $this->locationService->getActiveLocations();
        
        return Inertia::render('location/create', [
            'parentLocations' => $parentLocations->toArray(),
            'locationTypes' => config('features.location.types'),
            'capabilities' => config('features.location.capabilities'),
            'timezones' => \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA),
            'currencies' => ['CLP' => 'Chilean Peso', 'USD' => 'US Dollar'],
        ]);
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request)
    {
        $data = CreateLocationData::validateAndCreate($request);
        $location = $this->locationService->createLocation($data);
        
        return redirect()->route('locations.show', $location->id)
            ->with('success', 'Location created successfully.');
    }

    /**
     * Display the specified location.
     */
    public function show(int $id): Response
    {
        $location = $this->locationService->getLocation($id);
        $statistics = $this->locationService->getLocationStatistics($id);
        
        return Inertia::render('location/show', [
            'location' => $location->toArray(),
            'statistics' => $statistics,
            'canEdit' => true, // TODO: Add permission check
            'canDelete' => !$location->isDefault, // Cannot delete default location
        ]);
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit(int $id): Response
    {
        $location = $this->locationService->getLocation($id);
        $parentLocations = $this->locationService->getActiveLocations()
            ->filter(fn($loc) => $loc->id !== $id); // Exclude self from parent options
        
        return Inertia::render('location/edit', [
            'location' => $location->toArray(),
            'parentLocations' => $parentLocations->toArray(),
            'locationTypes' => config('features.location.types'),
            'capabilities' => config('features.location.capabilities'),
            'timezones' => \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA),
            'currencies' => ['CLP' => 'Chilean Peso', 'USD' => 'US Dollar'],
        ]);
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, int $id)
    {
        $data = UpdateLocationData::validateAndCreate($request);
        $location = $this->locationService->updateLocation($id, $data);
        
        return redirect()->route('locations.show', $location->id)
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Remove the specified location.
     */
    public function destroy(int $id)
    {
        $this->locationService->deleteLocation($id);
        
        return redirect()->route('locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    /**
     * Show location users management page.
     */
    public function users(int $id): Response
    {
        $location = $this->locationService->getLocation($id);
        // This would need integration with user management
        
        return Inertia::render('location/users', [
            'location' => $location->toArray(),
            'users' => [], // TODO: Load location users
            'availableUsers' => [], // TODO: Load users not in this location
        ]);
    }

    /**
     * Show location settings page.
     */
    public function settings(int $id): Response
    {
        $location = $this->locationService->getLocation($id);
        $settings = $this->locationService->getLocationSettings($id);
        
        return Inertia::render('location/settings', [
            'location' => $location->toArray(),
            'settings' => $settings,
        ]);
    }

    /**
     * Update location settings.
     */
    public function updateSettings(Request $request, int $id)
    {
        $data = UpdateLocationSettingsData::validateAndCreate($request);
        
        $this->locationService->updateLocationSettings($id, $data->settings);
        
        return back()->with('success', 'Settings updated successfully.');
    }
    
    /**
     * Display location types overview.
     */
    public function types(): Response
    {
        $locationTypes = LocationType::options();
        
        // Get statistics for each location type
        $typeStats = [];
        foreach (LocationType::cases() as $type) {
            $count = $this->locationService->getLocationsByType($type->value)->count();
            $typeStats[$type->value] = [
                'count' => $count,
                'type' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
                'icon' => $type->icon(),
                'capabilities' => $type->defaultCapabilities(),
                'supportsOrders' => $type->supportsOrders(),
            ];
        }
        
        return Inertia::render('location/types', [
            'locationTypes' => $locationTypes,
            'typeStatistics' => $typeStats,
            'totalLocations' => array_sum(array_column($typeStats, 'count')),
        ]);
    }
    
    /**
     * Display location hierarchy view.
     */
    public function hierarchy(): Response
    {
        $hierarchy = $this->locationService->getLocationHierarchy();
        
        return Inertia::render('location/hierarchy', [
            'hierarchy' => $hierarchy->toArray(),
            'canEdit' => true, // TODO: Add permission check
        ]);
    }
    
    /**
     * Display general location settings.
     */
    public function generalSettings(): Response
    {
        // Global location settings that apply to all locations
        $settings = [
            'defaultTimezone' => config('app.timezone'),
            'defaultCurrency' => config('features.location.default_currency', 'CLP'),
            'requireApproval' => config('features.location.require_approval', false),
            'autoAssignCode' => config('features.location.auto_assign_code', true),
            'codePrefix' => config('features.location.code_prefix', 'LOC'),
        ];
        
        return Inertia::render('location/settings', [
            'settings' => $settings,
            'timezones' => \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA),
            'currencies' => ['CLP' => 'Chilean Peso', 'USD' => 'US Dollar'],
        ]);
    }
}