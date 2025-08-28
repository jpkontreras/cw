<?php

declare(strict_types=1);

namespace Colame\Business\Services;

use Colame\Business\Contracts\BusinessContextInterface;
use Colame\Business\Contracts\BusinessRepositoryInterface;
use Colame\Business\Contracts\BusinessUserRepositoryInterface;
use Colame\Business\Data\BusinessData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BusinessContextService implements BusinessContextInterface
{
    private ?BusinessData $currentBusiness = null;
    private ?array $cachedBusinesses = null;

    public function __construct(
        private readonly BusinessRepositoryInterface $businessRepository,
        private readonly BusinessUserRepositoryInterface $userRepository,
    ) {}

    /**
     * Get the current business context
     */
    public function getCurrentBusiness(): ?BusinessData
    {
        if ($this->currentBusiness !== null) {
            return $this->currentBusiness;
        }

        $businessId = $this->getCurrentBusinessId();
        
        if (!$businessId) {
            return null;
        }

        $this->currentBusiness = $this->businessRepository->findWithRelations($businessId);
        
        return $this->currentBusiness;
    }

    /**
     * Get the current business ID
     */
    public function getCurrentBusinessId(): ?int
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        // First check session
        $sessionBusinessId = Session::get('current_business_id');
        if ($sessionBusinessId && $this->hasAccess($sessionBusinessId, $user->id)) {
            return $sessionBusinessId;
        }

        // Then check user's default from preferences table
        $userBusinessId = DB::table('user_business_preferences')
            ->where('user_id', $user->id)
            ->value('current_business_id');
            
        if ($userBusinessId && $this->hasAccess($userBusinessId, $user->id)) {
            Session::put('current_business_id', $userBusinessId);
            return $userBusinessId;
        }

        // Auto-switch to first available business if configured
        if (config('features.business.multi_tenancy.auto_switch_business', true)) {
            $businesses = $this->getAccessibleBusinesses();
            if (count($businesses) === 1) {
                $businessId = $businesses[0]->id;
                $this->setCurrentBusiness($businessId);
                return $businessId;
            }
        }

        return null;
    }

    /**
     * Set the current business context
     */
    public function setCurrentBusiness(int $businessId): void
    {
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        if (!$this->hasAccess($businessId, $user->id)) {
            throw new \Exception('You do not have access to this business.');
        }

        // Update session
        Session::put('current_business_id', $businessId);
        
        // Update user's default in preferences table
        DB::table('user_business_preferences')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'current_business_id' => $businessId,
                'updated_at' => now()
            ]
        );
        
        // Clear cache
        $this->currentBusiness = null;
        
        // Touch last accessed
        $businessUser = $this->userRepository->find($businessId, $user->id);
        if ($businessUser) {
            $businessUser->touchLastAccessed();
        }
    }

    /**
     * Clear the current business context
     */
    public function clearCurrentBusiness(): void
    {
        Session::forget('current_business_id');
        $this->currentBusiness = null;
        
        $user = Auth::user();
        if ($user) {
            DB::table('user_business_preferences')
                ->where('user_id', $user->id)
                ->update([
                    'current_business_id' => null,
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Check if a user has access to a business
     */
    public function hasAccess(int $businessId, int $userId): bool
    {
        return $this->userRepository->userBelongsToBusiness($businessId, $userId);
    }

    /**
     * Check if the current user has access to the current business
     */
    public function hasCurrentAccess(): bool
    {
        $user = Auth::user();
        $businessId = $this->getCurrentBusinessId();
        
        if (!$user || !$businessId) {
            return false;
        }

        return $this->hasAccess($businessId, $user->id);
    }

    /**
     * Get the user's role in the current business
     */
    public function getCurrentRole(): ?string
    {
        $user = Auth::user();
        $businessId = $this->getCurrentBusinessId();
        
        if (!$user || !$businessId) {
            return null;
        }

        return $this->userRepository->getUserRole($businessId, $user->id);
    }

    /**
     * Check if the current user is the owner of the current business
     */
    public function isOwner(): bool
    {
        $role = $this->getCurrentRole();
        return $role === 'owner';
    }

    /**
     * Check if the current user is an admin in the current business
     */
    public function isAdmin(): bool
    {
        $role = $this->getCurrentRole();
        return in_array($role, ['owner', 'admin']);
    }

    /**
     * Check if the current user can perform an action in the current business
     */
    public function can(string $permission): bool
    {
        $user = Auth::user();
        $businessId = $this->getCurrentBusinessId();
        
        if (!$user || !$businessId) {
            return false;
        }

        $businessUser = $this->userRepository->find($businessId, $user->id);
        
        if (!$businessUser) {
            return false;
        }

        return $businessUser->role->hasPermission($permission);
    }

    /**
     * Switch to a different business for the current user
     */
    public function switchBusiness(int $businessId): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        if (!$this->hasAccess($businessId, $user->id)) {
            return false;
        }

        $this->setCurrentBusiness($businessId);
        
        return true;
    }

    /**
     * Get all businesses the current user has access to
     */
    public function getAccessibleBusinesses(): array
    {
        if ($this->cachedBusinesses !== null) {
            return $this->cachedBusinesses;
        }

        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        $businesses = $this->businessRepository->getUserBusinesses($user->id);
        $this->cachedBusinesses = $businesses->toArray();
        
        return $this->cachedBusinesses;
    }

    /**
     * Get businesses owned by a specific user
     */
    public function getOwnedBusinesses(int $userId): array
    {
        $businesses = $this->businessRepository->findByOwnerId($userId);
        return $businesses->toArray();
    }

    /**
     * Get user's businesses with their role information
     */
    public function getUserBusinessesWithRoles(int $userId): array
    {
        $businessUsers = $this->userRepository->getUserBusinesses($userId);
        $businessesWithRoles = [];

        foreach ($businessUsers as $businessUser) {
            $business = $this->businessRepository->find($businessUser->businessId);
            if ($business) {
                $businessesWithRoles[] = [
                    'business' => $business,
                    'role' => $businessUser->role,
                    'isOwner' => $businessUser->isOwner ?? false,
                    'status' => $businessUser->status ?? 'active',
                ];
            }
        }

        return $businessesWithRoles;
    }

    /**
     * Get effective business for a user (current or first available)
     */
    public function getEffectiveBusiness(int $userId): ?BusinessData
    {
        // Try to get current business from preferences
        $currentBusinessId = DB::table('user_business_preferences')
            ->where('user_id', $userId)
            ->value('current_business_id');
        
        if ($currentBusinessId && $this->hasAccess($currentBusinessId, $userId)) {
            return $this->businessRepository->find($currentBusinessId);
        }

        // Return first available business
        $businesses = $this->businessRepository->getUserBusinesses($userId);
        return $businesses->first();
    }

    /**
     * Check if a user owns any business
     */
    public function ownsAnyBusiness(int $userId): bool
    {
        return DB::table('businesses')
            ->where('owner_id', $userId)
            ->exists();
    }

    /**
     * Get a user's role in a specific business
     */
    public function getUserRoleInBusiness(int $userId, int $businessId): ?string
    {
        return $this->userRepository->getUserRole($businessId, $userId);
    }
}