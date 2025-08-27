<?php

declare(strict_types=1);

namespace Colame\Business\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Business\Contracts\BusinessContextInterface;
use Colame\Business\Contracts\BusinessServiceInterface;
use Colame\Business\Data\CreateBusinessData;
use Colame\Business\Data\InviteUserData;
use Colame\Business\Data\UpdateBusinessData;
use Colame\Business\Exceptions\BusinessException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function __construct(
        private readonly BusinessServiceInterface $businessService,
        private readonly BusinessContextInterface $businessContext,
    ) {}

    /**
     * Get all businesses for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $businesses = $this->businessService->getUserBusinesses($request->user()->id);

        return response()->json([
            'data' => $businesses->toArray(),
            'current' => $this->businessContext->getCurrentBusinessId(),
        ]);
    }

    /**
     * Create a new business
     */
    public function store(Request $request): JsonResponse
    {
        $data = CreateBusinessData::validateAndCreate(
            array_merge($request->all(), ['ownerId' => $request->user()->id])
        );

        try {
            $business = $this->businessService->createBusiness($data);
            
            return response()->json([
                'data' => $business->toArray(),
                'message' => 'Business created successfully.',
            ], 201);
        } catch (BusinessException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get a specific business
     */
    public function show(int $id): JsonResponse
    {
        $business = $this->businessService->getBusiness($id);
        
        if (!$business) {
            return response()->json(['error' => 'Business not found.'], 404);
        }

        if (!$this->businessContext->hasAccess($id, request()->user()->id)) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        return response()->json(['data' => $business->toArray()]);
    }

    /**
     * Update a business
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->businessContext->can('manage_settings')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $data = UpdateBusinessData::validateAndCreate($request->all());

        try {
            $business = $this->businessService->updateBusiness($id, $data);
            
            return response()->json([
                'data' => $business->toArray(),
                'message' => 'Business updated successfully.',
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a business
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->businessContext->isOwner()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        try {
            $this->businessService->deleteBusiness($id);
            
            return response()->json([
                'message' => 'Business deleted successfully.',
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Switch to a different business
     */
    public function switch(int $id): JsonResponse
    {
        try {
            $this->businessContext->switchBusiness($id);
            
            return response()->json([
                'message' => 'Switched business successfully.',
                'current' => $id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get business metrics
     */
    public function metrics(int $id): JsonResponse
    {
        if (!$this->businessContext->hasAccess($id, request()->user()->id)) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $metrics = $this->businessService->getBusinessMetrics($id);

        return response()->json(['data' => $metrics->toArray()]);
    }

    /**
     * Get business users
     */
    public function users(int $id): JsonResponse
    {
        if (!$this->businessContext->hasAccess($id, request()->user()->id)) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $users = $this->businessService->getBusinessUsers($id);

        return response()->json(['data' => $users->toArray()]);
    }

    /**
     * Invite a user to the business
     */
    public function inviteUser(Request $request, int $id): JsonResponse
    {
        if (!$this->businessContext->can('manage_users')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $data = InviteUserData::validateAndCreate($request->all());

        try {
            $user = $this->businessService->inviteUser($id, $data);
            
            return response()->json([
                'data' => $user->toArray(),
                'message' => 'User invited successfully.',
            ], 201);
        } catch (BusinessException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove a user from the business
     */
    public function removeUser(int $businessId, int $userId): JsonResponse
    {
        if (!$this->businessContext->can('manage_users')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        try {
            $this->businessService->removeUser($businessId, $userId);
            
            return response()->json([
                'message' => 'User removed successfully.',
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a user's role
     */
    public function updateUserRole(Request $request, int $businessId, int $userId): JsonResponse
    {
        if (!$this->businessContext->can('manage_users')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:owner,admin,manager,member'],
        ]);

        try {
            $user = $this->businessService->updateUserRole($businessId, $userId, $validated['role']);
            
            return response()->json([
                'data' => $user->toArray(),
                'message' => 'User role updated successfully.',
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Accept an invitation
     */
    public function acceptInvitation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $user = $this->businessService->acceptInvitation($validated['token']);
            
            return response()->json([
                'data' => $user->toArray(),
                'message' => 'Invitation accepted successfully.',
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get current business context
     */
    public function current(): JsonResponse
    {
        $business = $this->businessContext->getCurrentBusiness();
        
        return response()->json([
            'data' => $business?->toArray(),
            'role' => $this->businessContext->getCurrentRole(),
            'accessible' => $this->businessContext->getAccessibleBusinesses(),
        ]);
    }
}