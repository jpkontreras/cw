<?php

declare(strict_types=1);

namespace Colame\Location\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Colame\Location\Services\UserLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles user-location relationship operations.
 * This controller manages the relationship between users and locations.
 */
class UserLocationController extends Controller
{
    public function __construct(
        private UserLocationService $userLocationService
    ) {}

    /**
     * Get all locations for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $locations = $this->userLocationService->getUserLocations($user);
        
        return response()->json([
            'locations' => $locations,
        ]);
    }

    /**
     * Get current location for the authenticated user
     */
    public function current(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $location = $this->userLocationService->getCurrentLocation($user);
        
        return response()->json([
            'location' => $location,
        ]);
    }

    /**
     * Get effective location for the authenticated user
     */
    public function effective(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $location = $this->userLocationService->getEffectiveLocation($user);
        
        return response()->json([
            'location' => $location,
        ]);
    }

    /**
     * Set current location for the authenticated user
     */
    public function setCurrent(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $validated = $request->validate([
            'location_id' => 'required|integer',
        ]);
        
        $success = $this->userLocationService->setCurrentLocation(
            $user,
            $validated['location_id']
        );
        
        if (!$success) {
            return response()->json([
                'message' => 'You do not have access to this location',
            ], 403);
        }
        
        return response()->json([
            'message' => 'Current location updated successfully',
        ]);
    }

    /**
     * Get locations with role information for the authenticated user
     */
    public function withRoles(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $locationsWithRoles = $this->userLocationService->getUserLocationsWithRoles($user);
        
        return response()->json([
            'locations' => $locationsWithRoles,
        ]);
    }

    /**
     * Check if user is a manager at any location
     */
    public function isManager(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        
        $isManager = $this->userLocationService->isManagerAtAnyLocation($user);
        
        return response()->json([
            'is_manager' => $isManager,
        ]);
    }
}