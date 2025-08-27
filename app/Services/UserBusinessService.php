<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Colame\Business\Contracts\BusinessServiceInterface;
use Spatie\LaravelData\DataCollection;

/**
 * Bridge service to access business data for users
 * without violating module boundaries
 */
class UserBusinessService
{
    public function __construct(
        private ?BusinessServiceInterface $businessService = null
    ) {}

    /**
     * Get all businesses for a user
     */
    public function getUserBusinesses(User $user): DataCollection
    {
        if (!$this->businessService) {
            return DataCollection::empty();
        }

        return $this->businessService->getUserBusinesses($user->id);
    }

    /**
     * Get user's current business ID
     */
    public function getCurrentBusinessId(User $user): ?int
    {
        return $user->current_business_id;
    }

    /**
     * Check if user has access to a business
     */
    public function hasAccessToBusiness(User $user, int $businessId): bool
    {
        if (!$this->businessService) {
            return false;
        }

        $businesses = $this->businessService->getUserBusinesses($user->id);
        
        foreach ($businesses as $business) {
            if ($business->id === $businessId) {
                return true;
            }
        }

        return false;
    }
}