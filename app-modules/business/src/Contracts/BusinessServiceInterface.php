<?php

declare(strict_types=1);

namespace Colame\Business\Contracts;

use Colame\Business\Data\BusinessData;
use Colame\Business\Data\BusinessMetricsData;
use Colame\Business\Data\BusinessUserData;
use Colame\Business\Data\CreateBusinessData;
use Colame\Business\Data\InviteUserData;
use Colame\Business\Data\UpdateBusinessData;
use Spatie\LaravelData\DataCollection;

interface BusinessServiceInterface
{
    /**
     * Create a new business
     */
    public function createBusiness(CreateBusinessData $data): BusinessData;

    /**
     * Update a business
     */
    public function updateBusiness(int $businessId, UpdateBusinessData $data): BusinessData;

    /**
     * Delete a business
     */
    public function deleteBusiness(int $businessId): bool;

    /**
     * Get a business by ID
     */
    public function getBusiness(int $businessId): ?BusinessData;

    /**
     * Get a business by slug
     */
    public function getBusinessBySlug(string $slug): ?BusinessData;

    /**
     * Get businesses for a user
     * 
     * @return DataCollection<BusinessData>
     */
    public function getUserBusinesses(int $userId): DataCollection;

    /**
     * Add a user to a business
     */
    public function addUser(int $businessId, int $userId, string $role = 'member'): BusinessUserData;

    /**
     * Remove a user from a business
     */
    public function removeUser(int $businessId, int $userId): bool;

    /**
     * Update a user's role in a business
     */
    public function updateUserRole(int $businessId, int $userId, string $role): BusinessUserData;

    /**
     * Get users in a business
     * 
     * @return DataCollection<BusinessUserData>
     */
    public function getBusinessUsers(int $businessId): DataCollection;

    /**
     * Invite a user to a business
     */
    public function inviteUser(int $businessId, InviteUserData $data): BusinessUserData;

    /**
     * Accept an invitation to a business
     */
    public function acceptInvitation(string $token): BusinessUserData;

    /**
     * Switch the current business for a user
     */
    public function switchBusiness(int $businessId, int $userId): void;

    /**
     * Get business metrics and statistics
     */
    public function getBusinessMetrics(int $businessId): BusinessMetricsData;

    /**
     * Check if a business has reached its limits
     */
    public function hasReachedLimit(int $businessId, string $resource): bool;

    /**
     * Get the current usage for a business
     */
    public function getUsage(int $businessId): array;

    /**
     * Update business subscription
     */
    public function updateSubscription(int $businessId, string $planId): bool;

    /**
     * Generate a unique slug for a business
     */
    public function generateSlug(string $name): string;

    /**
     * Transfer business ownership
     */
    public function transferOwnership(int $businessId, int $newOwnerId): bool;
    
    /**
     * Update business settings (branding, features, notifications, etc.)
     */
    public function updateBusinessSettings(int $businessId, array $settings): BusinessData;
}