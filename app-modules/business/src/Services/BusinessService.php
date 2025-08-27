<?php

declare(strict_types=1);

namespace Colame\Business\Services;

use App\Models\User;
use Colame\Business\Contracts\BusinessRepositoryInterface;
use Colame\Business\Contracts\BusinessServiceInterface;
use Colame\Business\Contracts\BusinessUserRepositoryInterface;
use Colame\Business\Data\BusinessData;
use Colame\Business\Data\BusinessMetricsData;
use Colame\Business\Data\BusinessUserData;
use Colame\Business\Data\CreateBusinessData;
use Colame\Business\Data\InviteUserData;
use Colame\Business\Data\UpdateBusinessData;
use Colame\Business\Enums\SubscriptionTier;
use Colame\Business\Exceptions\BusinessException;
use Colame\Business\Exceptions\BusinessLimitExceededException;
use Colame\Business\Exceptions\BusinessNotFoundException;
use Colame\Business\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;

class BusinessService implements BusinessServiceInterface
{
    public function __construct(
        private readonly BusinessRepositoryInterface $businessRepository,
        private readonly BusinessUserRepositoryInterface $userRepository,
    ) {}

    /**
     * Create a new business
     */
    public function createBusiness(CreateBusinessData $data): BusinessData
    {
        // Check if user has reached their business limit
        $userBusinessCount = $this->businessRepository->countUserBusinesses($data->ownerId);
        $maxBusinesses = config('features.business.limits.businesses_per_user', 5);
        
        if ($userBusinessCount >= $maxBusinesses) {
            throw new BusinessLimitExceededException('You have reached the maximum number of businesses allowed.');
        }

        // Ensure slug is unique
        if ($this->businessRepository->slugExists($data->slug)) {
            $data = CreateBusinessData::from([
                ...$data->toArray(),
                'slug' => $this->generateSlug($data->name),
            ]);
        }

        return DB::transaction(function () use ($data) {
            // Create the business
            $business = $this->businessRepository->create($data);

            // Set as current business for the owner
            User::where('id', $data->ownerId)->update([
                'current_business_id' => $business->id,
            ]);

            return $business;
        });
    }

    /**
     * Update a business
     */
    public function updateBusiness(int $businessId, UpdateBusinessData $data): BusinessData
    {
        $business = $this->businessRepository->find($businessId);
        
        if (!$business) {
            throw new BusinessNotFoundException("Business with ID {$businessId} not found.");
        }

        return $this->businessRepository->update($businessId, $data);
    }

    /**
     * Delete a business
     */
    public function deleteBusiness(int $businessId): bool
    {
        $business = $this->businessRepository->find($businessId);
        
        if (!$business) {
            throw new BusinessNotFoundException("Business with ID {$businessId} not found.");
        }

        // Check if this is the last business for any users
        $users = $this->userRepository->getBusinessUsers($businessId);
        
        foreach ($users as $user) {
            if ($this->businessRepository->countUserBusinesses($user->userId) === 1) {
                throw new BusinessException("Cannot delete the last business for user {$user->userName}.");
            }
        }

        return DB::transaction(function () use ($businessId) {
            // Update users who have this as their current business
            User::where('current_business_id', $businessId)->update([
                'current_business_id' => null,
            ]);

            return $this->businessRepository->delete($businessId);
        });
    }

    /**
     * Get a business by ID
     */
    public function getBusiness(int $businessId): ?BusinessData
    {
        return $this->businessRepository->findWithRelations($businessId);
    }

    /**
     * Get a business by slug
     */
    public function getBusinessBySlug(string $slug): ?BusinessData
    {
        return $this->businessRepository->findBySlug($slug);
    }

    /**
     * Get businesses for a user
     */
    public function getUserBusinesses(int $userId): DataCollection
    {
        return $this->businessRepository->getUserBusinesses($userId);
    }

    /**
     * Add a user to a business
     */
    public function addUser(int $businessId, int $userId, string $role = 'member'): BusinessUserData
    {
        // Check if user already belongs to the business
        if ($this->userRepository->userBelongsToBusiness($businessId, $userId)) {
            throw new BusinessException('User already belongs to this business.');
        }

        // Check business user limit
        $business = $this->businessRepository->find($businessId);
        if ($business && $business->hasReachedLimit('users')) {
            throw new BusinessLimitExceededException('Business has reached its user limit.');
        }

        return $this->userRepository->addUser($businessId, $userId, [
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove a user from a business
     */
    public function removeUser(int $businessId, int $userId): bool
    {
        // Check if this is the owner
        $businessUser = $this->userRepository->find($businessId, $userId);
        
        if ($businessUser && $businessUser->isOwner) {
            throw new BusinessException('Cannot remove the owner from the business.');
        }

        // Update current business if needed
        $user = User::find($userId);
        if ($user && $user->current_business_id === $businessId) {
            $user->current_business_id = null;
            $user->save();
        }

        return $this->userRepository->removeUser($businessId, $userId);
    }

    /**
     * Update a user's role in a business
     */
    public function updateUserRole(int $businessId, int $userId, string $role): BusinessUserData
    {
        // Check if trying to change owner role
        $currentUser = $this->userRepository->find($businessId, $userId);
        
        if ($currentUser && $currentUser->isOwner && $role !== 'owner') {
            throw new BusinessException('Cannot change role of business owner.');
        }

        return $this->userRepository->updateUserRole($businessId, $userId, $role);
    }

    /**
     * Get users in a business
     */
    public function getBusinessUsers(int $businessId): DataCollection
    {
        return $this->userRepository->getBusinessUsers($businessId);
    }

    /**
     * Invite a user to a business
     */
    public function inviteUser(int $businessId, InviteUserData $data): BusinessUserData
    {
        // Check if user already exists
        $user = User::where('email', $data->email)->first();
        
        if ($user) {
            // User exists, check if already in business
            if ($this->userRepository->userBelongsToBusiness($businessId, $user->id)) {
                throw new BusinessException('User already belongs to this business.');
            }

            // Add user directly
            return $this->addUser($businessId, $user->id, $data->role);
        }

        // Create pending invitation
        $invitationToken = Str::random(32);
        
        $businessUser = $this->userRepository->addUser($businessId, 0, [ // 0 as placeholder user_id
            'role' => $data->role,
            'status' => 'pending',
            'invitation_token' => $invitationToken,
            'invited_at' => now(),
            'invited_by' => auth()->id(),
        ]);

        // Send invitation email
        // Mail::to($data->email)->send(new BusinessInvitation($businessUser, $data->message));

        return $businessUser;
    }

    /**
     * Accept an invitation to a business
     */
    public function acceptInvitation(string $token): BusinessUserData
    {
        $invitation = $this->userRepository->findByInvitationToken($token);
        
        if (!$invitation) {
            throw new BusinessException('Invalid or expired invitation token.');
        }

        // Check expiry
        $expiryDays = config('features.business.invitations.expiry_days', 7);
        if ($invitation->invitedAt->addDays($expiryDays)->isPast()) {
            throw new BusinessException('This invitation has expired.');
        }

        return $this->userRepository->acceptInvitation($token);
    }

    /**
     * Switch the current business for a user
     */
    public function switchBusiness(int $businessId, int $userId): void
    {
        // Verify user has access
        if (!$this->userRepository->userBelongsToBusiness($businessId, $userId)) {
            throw new BusinessException('You do not have access to this business.');
        }

        // Update user's current business
        User::where('id', $userId)->update([
            'current_business_id' => $businessId,
        ]);

        // Update last accessed time
        $this->userRepository->find($businessId, $userId)?->touchLastAccessed();
    }

    /**
     * Get business metrics and statistics
     */
    public function getBusinessMetrics(int $businessId): BusinessMetricsData
    {
        $business = Business::with([
            'users',
            'locations',
            'currentSubscription',
        ])->findOrFail($businessId);

        // Calculate metrics
        $totalUsers = $business->users()->where('status', 'active')->count();
        $totalLocations = $business->locations()->count();
        
        // These will be calculated when respective modules are updated
        $totalOrders = 0;
        $totalItems = 0;
        $totalStaff = 0;
        $totalRevenue = 0.0;
        
        $ordersByStatus = [
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        $subscription = $business->currentSubscription;
        $subscriptionStatus = $subscription ? $subscription->status : 'none';
        $daysUntilRenewal = $subscription && $subscription->next_payment_at 
            ? now()->diffInDays($subscription->next_payment_at, false) 
            : null;

        $currentUsage = [
            'locations' => $totalLocations,
            'users' => $totalUsers,
            'items' => $totalItems,
            'orders' => $totalOrders,
        ];

        $usageLimits = $business->limits ?? [];
        
        // Calculate usage percentage
        $usagePercentage = 0.0;
        if (!empty($usageLimits)) {
            $limitedResources = array_filter($usageLimits, fn($limit) => $limit !== null);
            if (count($limitedResources) > 0) {
                $percentages = [];
                foreach ($limitedResources as $resource => $limit) {
                    $current = $currentUsage[$resource] ?? 0;
                    $percentages[] = $limit > 0 ? ($current / $limit) * 100 : 0;
                }
                $usagePercentage = array_sum($percentages) / count($percentages);
            }
        }

        return new BusinessMetricsData(
            businessId: $businessId,
            totalUsers: $totalUsers,
            totalLocations: $totalLocations,
            totalOrders: $totalOrders,
            totalItems: $totalItems,
            totalStaff: $totalStaff,
            totalRevenue: $totalRevenue,
            ordersByStatus: $ordersByStatus,
            recentActivity: [], // To be implemented
            usageLimits: $usageLimits,
            currentUsage: $currentUsage,
            usagePercentage: $usagePercentage,
            subscriptionStatus: $subscriptionStatus,
            daysUntilRenewal: $daysUntilRenewal,
            monthlyStats: [], // To be implemented
        );
    }

    /**
     * Check if a business has reached its limits
     */
    public function hasReachedLimit(int $businessId, string $resource): bool
    {
        $business = $this->businessRepository->find($businessId);
        
        if (!$business) {
            return false;
        }

        $limit = $business->limits[$resource] ?? null;
        
        if ($limit === null) {
            return false; // No limit or unlimited
        }

        $current = match($resource) {
            'locations' => Business::find($businessId)->locations()->count(),
            'users' => $this->userRepository->countBusinessUsers($businessId),
            default => 0,
        };

        return $current >= $limit;
    }

    /**
     * Get the current usage for a business
     */
    public function getUsage(int $businessId): array
    {
        $business = Business::with(['users', 'locations'])->find($businessId);
        
        if (!$business) {
            return [];
        }

        return [
            'locations' => $business->locations->count(),
            'users' => $business->users()->where('status', 'active')->count(),
            'items' => 0, // To be implemented
            'orders' => 0, // To be implemented
        ];
    }

    /**
     * Update business subscription
     */
    public function updateSubscription(int $businessId, string $planId): bool
    {
        $business = Business::find($businessId);
        
        if (!$business) {
            throw new BusinessNotFoundException("Business with ID {$businessId} not found.");
        }

        // Update subscription tier
        $business->subscription_tier = $planId;
        
        // Update limits and features based on new tier
        $tier = SubscriptionTier::from($planId);
        $business->limits = $tier->limits();
        $business->features = $tier->features();
        
        $business->save();

        // Create or update subscription record
        // This would integrate with payment provider

        return true;
    }

    /**
     * Generate a unique slug for a business
     */
    public function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while ($this->businessRepository->slugExists($slug)) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Transfer business ownership
     */
    public function transferOwnership(int $businessId, int $newOwnerId): bool
    {
        return DB::transaction(function () use ($businessId, $newOwnerId) {
            $business = Business::findOrFail($businessId);
            $oldOwnerId = $business->owner_id;

            // Update business owner
            $business->owner_id = $newOwnerId;
            $business->save();

            // Update user roles
            // Remove old owner flag
            $this->userRepository->find($businessId, $oldOwnerId)?->update([
                'is_owner' => false,
                'role' => 'admin',
            ]);

            // Set new owner flag
            if ($this->userRepository->userBelongsToBusiness($businessId, $newOwnerId)) {
                $this->userRepository->find($businessId, $newOwnerId)?->update([
                    'is_owner' => true,
                    'role' => 'owner',
                ]);
            } else {
                // Add new owner to business
                $this->userRepository->addUser($businessId, $newOwnerId, [
                    'role' => 'owner',
                    'is_owner' => true,
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            }

            return true;
        });
    }
    
    /**
     * Create a subscription for a business
     */
    public function createSubscription(int $businessId, array $data): bool
    {
        $business = Business::findOrFail($businessId);
        
        // Set subscription details on the business
        $business->subscription_tier = $data['tier'] ?? 'starter';
        $business->trial_ends_at = $data['trialEndsAt'] ?? now()->addDays(30);
        $business->subscription_ends_at = $data['endsAt'] ?? null;
        
        // Set default features and limits based on tier
        switch ($business->subscription_tier) {
            case 'enterprise':
                $business->features = ['orders', 'inventory', 'reports', 'online_ordering', 'reservations', 'loyalty'];
                $business->limits = [
                    'maxLocations' => null,
                    'maxUsers' => null,
                    'maxProducts' => null,
                    'maxMonthlyOrders' => null,
                ];
                break;
            case 'professional':
                $business->features = ['orders', 'inventory', 'reports', 'online_ordering'];
                $business->limits = [
                    'maxLocations' => 10,
                    'maxUsers' => 50,
                    'maxProducts' => 1000,
                    'maxMonthlyOrders' => 10000,
                ];
                break;
            case 'starter':
            default:
                $business->features = ['orders', 'inventory', 'reports'];
                $business->limits = [
                    'maxLocations' => 3,
                    'maxUsers' => 10,
                    'maxProducts' => 100,
                    'maxMonthlyOrders' => 1000,
                ];
                break;
        }
        
        $business->save();
        
        // In a real implementation, you would also create a subscription record
        // in a subscriptions table and integrate with payment providers
        
        return true;
    }
    
    /**
     * Update business settings (branding, features, notifications, etc.)
     */
    public function updateBusinessSettings(int $businessId, array $settings): BusinessData
    {
        $business = Business::findOrFail($businessId);
        
        // Update specific settings based on what's provided
        if (isset($settings['primaryColor'])) {
            $business->primary_color = $settings['primaryColor'];
        }
        
        if (isset($settings['secondaryColor'])) {
            $business->secondary_color = $settings['secondaryColor'];
        }
        
        if (isset($settings['logoUrl'])) {
            $business->logo_url = $settings['logoUrl'];
        }
        
        if (isset($settings['features'])) {
            $business->features = $settings['features'];
        }
        
        if (isset($settings['settings'])) {
            $existingSettings = $business->settings ?? [];
            $business->settings = array_merge($existingSettings, $settings['settings']);
        }
        
        $business->save();
        
        return BusinessData::fromModel($business);
    }
}