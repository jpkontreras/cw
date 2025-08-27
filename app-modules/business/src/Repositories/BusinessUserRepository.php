<?php

declare(strict_types=1);

namespace Colame\Business\Repositories;

use Colame\Business\Contracts\BusinessUserRepositoryInterface;
use Colame\Business\Data\BusinessUserData;
use Colame\Business\Models\BusinessUser;
use Spatie\LaravelData\DataCollection;

class BusinessUserRepository implements BusinessUserRepositoryInterface
{
    /**
     * Find a business user relationship
     */
    public function find(int $businessId, int $userId): ?BusinessUserData
    {
        $businessUser = BusinessUser::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->with(['business', 'user'])
            ->first();

        return $businessUser ? BusinessUserData::fromModel($businessUser) : null;
    }

    /**
     * Get all users for a business
     */
    public function getBusinessUsers(int $businessId): DataCollection
    {
        $users = BusinessUser::where('business_id', $businessId)
            ->with(['user', 'inviter'])
            ->get();

        return BusinessUserData::collect($users, DataCollection::class);
    }

    /**
     * Get all businesses for a user
     */
    public function getUserBusinesses(int $userId): DataCollection
    {
        $businesses = BusinessUser::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['business'])
            ->get();

        return BusinessUserData::collect($businesses, DataCollection::class);
    }

    /**
     * Add a user to a business
     */
    public function addUser(int $businessId, int $userId, array $data): BusinessUserData
    {
        $businessUser = BusinessUser::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'role' => $data['role'] ?? 'member',
            'permissions' => $data['permissions'] ?? null,
            'status' => $data['status'] ?? 'active',
            'is_owner' => $data['is_owner'] ?? false,
            'invitation_token' => $data['invitation_token'] ?? null,
            'invited_at' => $data['invited_at'] ?? null,
            'joined_at' => $data['status'] === 'active' ? now() : null,
            'invited_by' => $data['invited_by'] ?? null,
            'preferences' => $data['preferences'] ?? null,
        ]);

        return BusinessUserData::fromModel($businessUser->fresh(['user', 'business']));
    }

    /**
     * Update a user's role in a business
     */
    public function updateUserRole(int $businessId, int $userId, string $role): BusinessUserData
    {
        $businessUser = BusinessUser::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $businessUser->update(['role' => $role]);

        return BusinessUserData::fromModel($businessUser->fresh(['user', 'business']));
    }

    /**
     * Remove a user from a business
     */
    public function removeUser(int $businessId, int $userId): bool
    {
        return BusinessUser::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Find by invitation token
     */
    public function findByInvitationToken(string $token): ?BusinessUserData
    {
        $businessUser = BusinessUser::where('invitation_token', $token)
            ->where('status', 'pending')
            ->with(['business', 'user', 'inviter'])
            ->first();

        return $businessUser ? BusinessUserData::fromModel($businessUser) : null;
    }

    /**
     * Accept an invitation
     */
    public function acceptInvitation(string $token): BusinessUserData
    {
        $businessUser = BusinessUser::where('invitation_token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $businessUser->acceptInvitation();

        return BusinessUserData::fromModel($businessUser->fresh(['user', 'business']));
    }

    /**
     * Check if a user belongs to a business
     */
    public function userBelongsToBusiness(int $businessId, int $userId): bool
    {
        return BusinessUser::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get user's role in a business
     */
    public function getUserRole(int $businessId, int $userId): ?string
    {
        $businessUser = BusinessUser::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        return $businessUser?->role;
    }

    /**
     * Count users in a business
     */
    public function countBusinessUsers(int $businessId): int
    {
        return BusinessUser::where('business_id', $businessId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get business owners
     */
    public function getBusinessOwners(int $businessId): DataCollection
    {
        $owners = BusinessUser::where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('is_owner', true)
                      ->orWhere('role', 'owner');
            })
            ->with(['user'])
            ->get();

        return BusinessUserData::collect($owners, DataCollection::class);
    }
}