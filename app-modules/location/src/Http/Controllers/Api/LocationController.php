<?php

declare(strict_types=1);

namespace Colame\Location\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Location\Contracts\LocationServiceInterface;
use Colame\Location\Data\CreateLocationData;
use Colame\Location\Data\UpdateLocationData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationServiceInterface $locationService
    ) {}

    /**
     * Display a listing of locations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // If user is provided, get their accessible locations
        if ($user) {
            $locations = $this->locationService->getUserAccessibleLocations($user->id);
        } else {
            // Otherwise, get all active locations
            $locations = $this->locationService->getActiveLocations();
        }
        
        return response()->json([
            'data' => $locations->toArray(),
        ]);
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request): JsonResponse
    {
        $data = CreateLocationData::validateAndCreate($request);
        $location = $this->locationService->createLocation($data);
        
        return response()->json([
            'data' => $location->toArray(),
            'message' => 'Location created successfully.',
        ], 201);
    }

    /**
     * Display the specified location.
     */
    public function show(int $id): JsonResponse
    {
        $location = $this->locationService->getLocation($id);
        
        return response()->json([
            'data' => $location->toArray(),
        ]);
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = UpdateLocationData::validateAndCreate($request);
        $location = $this->locationService->updateLocation($id, $data);
        
        return response()->json([
            'data' => $location->toArray(),
            'message' => 'Location updated successfully.',
        ]);
    }

    /**
     * Remove the specified location.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->locationService->deleteLocation($id);
        
        return response()->json([
            'message' => 'Location deleted successfully.',
        ]);
    }

    /**
     * Set user's current location.
     */
    public function setCurrent(Request $request): JsonResponse
    {
        $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
        ]);
        
        $user = $request->user();
        $locationId = $request->input('location_id');
        
        $this->locationService->setUserCurrentLocation($user->id, $locationId);
        
        $location = $this->locationService->getLocation($locationId);
        
        return response()->json([
            'data' => $location->toArray(),
            'message' => 'Current location updated successfully.',
        ]);
    }

    /**
     * Get user's current location.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $location = $this->locationService->getUserCurrentLocation($user->id);
        
        if (!$location) {
            // Try to get default location
            $location = $this->locationService->getUserDefaultLocation($user->id);
        }
        
        if (!$location) {
            // Get system default
            $location = $this->locationService->getDefaultLocation();
        }
        
        return response()->json([
            'data' => $location->toArray(),
        ]);
    }

    /**
     * Get locations by capability.
     */
    public function byCapability(Request $request): JsonResponse
    {
        $request->validate([
            'capability' => 'required|string|in:dine_in,takeout,delivery,catering',
        ]);
        
        $locations = $this->locationService->getLocationsByCapability($request->input('capability'));
        
        return response()->json([
            'data' => $locations->toArray(),
        ]);
    }

    /**
     * Check if location is open.
     */
    public function checkOpen(int $id): JsonResponse
    {
        $isOpen = $this->locationService->isLocationOpen($id);
        $location = $this->locationService->getLocation($id);
        
        return response()->json([
            'data' => [
                'location_id' => $id,
                'is_open' => $isOpen,
                'opening_hours' => $location->openingHours,
                'timezone' => $location->timezone,
            ],
        ]);
    }

    /**
     * Validate location code.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'exclude_id' => 'nullable|integer',
        ]);
        
        $isValid = $this->locationService->validateLocationCode(
            $request->input('code'),
            $request->input('exclude_id')
        );
        
        return response()->json([
            'data' => [
                'code' => $request->input('code'),
                'is_valid' => $isValid,
            ],
        ]);
    }
}