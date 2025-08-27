<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Colame\Business\Contracts\BusinessRepositoryInterface;
use Colame\Business\Contracts\BusinessUserRepositoryInterface;
use Colame\Business\Data\BusinessData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service to handle user-business relationships
 * Maintains proper module boundaries by using interfaces instead of models
 */
class UserBusinessService
{
    public function __construct(
        private ?BusinessRepositoryInterface $businessRepository = null,
        private ?BusinessUserRepositoryInterface $businessUserRepository = null
    ) {}

    /**
     * Get all businesses accessible by a user
     */
    public function getUserBusinesses(User $user): Collection
    {
        if (!$this->businessUserRepository) {
            return collect();
        }

        $dataCollection = $this->businessUserRepository->getUserBusinesses($user->id);
        
        // Convert DataCollection to regular Collection
        return collect($dataCollection->toArray());
    }

    /**
     * Get user's current business
     */
    public function getCurrentBusiness(User $user): ?BusinessData
    {
        if (!$this->businessRepository) {
            return null;
        }
        
        if (!$user->current_business_id) {
            return null;
        }

        return $this->businessRepository->find($user->current_business_id);
    }

    /**
     * Check if user has access to a specific business
     */
    public function hasAccessToBusiness(User $user, int $businessId): bool
    {
        if (!$this->businessUserRepository) {
            return false;
        }

        return $this->businessUserRepository->userBelongsToBusiness($user->id, $businessId);
    }

    /**
     * Get user's role in a specific business
     */
    public function getRoleInBusiness(User $user, int $businessId): ?string
    {
        return DB::table('business_users')
            ->where('user_id', $user->id)
            ->where('business_id', $businessId)
            ->value('role');
    }

    /**
     * Set user's current business
     */
    public function setCurrentBusiness(User $user, int $businessId): bool
    {
        if (!$this->hasAccessToBusiness($user, $businessId)) {
            return false;
        }

        $user->current_business_id = $businessId;
        return $user->save();
    }

    /**
     * Check if user owns any business
     */
    public function ownsAnyBusiness(User $user): bool
    {
        return DB::table('businesses')
            ->where('owner_id', $user->id)
            ->exists();
    }

    /**
     * Get businesses owned by the user
     */
    public function getOwnedBusinesses(User $user): Collection
    {
        if (!$this->businessRepository) {
            return collect();
        }

        $businessIds = DB::table('businesses')
            ->where('owner_id', $user->id)
            ->pluck('id')
            ->toArray();

        $businesses = collect();
        foreach ($businessIds as $businessId) {
            $business = $this->businessRepository->find($businessId);
            if ($business) {
                $businesses->push($business);
            }
        }

        return $businesses;
    }

    /**
     * Get businesses with user's role information
     */
    public function getUserBusinessesWithRoles(User $user): Collection
    {
        if (!$this->businessRepository) {
            return collect();
        }

        $pivotData = DB::table('business_users')
            ->where('user_id', $user->id)
            ->get(['business_id', 'role', 'is_owner', 'status']);

        $businessesWithRoles = collect();
        foreach ($pivotData as $pivot) {
            $business = $this->businessRepository->find($pivot->business_id);
            if ($business) {
                $businessesWithRoles->push([
                    'business' => $business,
                    'role' => $pivot->role,
                    'isOwner' => $pivot->is_owner,
                    'status' => $pivot->status,
                ]);
            }
        }

        return $businessesWithRoles;
    }

    /**
     * Get effective business (current or first available)
     */
    public function getEffectiveBusiness(User $user): ?BusinessData
    {
        // Try current business
        if ($business = $this->getCurrentBusiness($user)) {
            return $business;
        }

        // Return first available business
        $businesses = $this->getUserBusinesses($user);
        return $businesses->first();
    }
}