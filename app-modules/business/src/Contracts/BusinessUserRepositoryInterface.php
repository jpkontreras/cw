<?php

declare(strict_types=1);

namespace Colame\Business\Contracts;

use Colame\Business\Data\BusinessUserData;
use Spatie\LaravelData\DataCollection;

interface BusinessUserRepositoryInterface
{
    /**
     * Find a business user relationship
     */
    public function find(int $businessId, int $userId): ?BusinessUserData;

    /**
     * Get all users for a business
     * 
     * @return DataCollection<BusinessUserData>
     */
    public function getBusinessUsers(int $businessId): DataCollection;

    /**
     * Get all businesses for a user
     * 
     * @return DataCollection<BusinessUserData>
     */
    public function getUserBusinesses(int $userId): DataCollection;

    /**
     * Add a user to a business
     */
    public function addUser(int $businessId, int $userId, array $data): BusinessUserData;

    /**
     * Update a user's role in a business
     */
    public function updateUserRole(int $businessId, int $userId, string $role): BusinessUserData;

    /**
     * Remove a user from a business
     */
    public function removeUser(int $businessId, int $userId): bool;

    /**
     * Find by invitation token
     */
    public function findByInvitationToken(string $token): ?BusinessUserData;

    /**
     * Accept an invitation
     */
    public function acceptInvitation(string $token): BusinessUserData;

    /**
     * Check if a user belongs to a business
     */
    public function userBelongsToBusiness(int $businessId, int $userId): bool;

    /**
     * Get user's role in a business
     */
    public function getUserRole(int $businessId, int $userId): ?string;

    /**
     * Count users in a business
     */
    public function countBusinessUsers(int $businessId): int;

    /**
     * Get business owners
     * 
     * @return DataCollection<BusinessUserData>
     */
    public function getBusinessOwners(int $businessId): DataCollection;

    /**
     * Update user's last accessed timestamp for a business
     */
    public function updateLastAccessed(int $businessId, int $userId): void;
}