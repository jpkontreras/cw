<?php

declare(strict_types=1);

namespace Colame\Business\Contracts;

use Colame\Business\Data\BusinessData;

interface BusinessContextInterface
{
    /**
     * Get the current business context
     */
    public function getCurrentBusiness(): ?BusinessData;

    /**
     * Get the current business ID
     */
    public function getCurrentBusinessId(): ?int;

    /**
     * Set the current business context
     */
    public function setCurrentBusiness(int $businessId): void;

    /**
     * Clear the current business context
     */
    public function clearCurrentBusiness(): void;

    /**
     * Check if a user has access to a business
     */
    public function hasAccess(int $businessId, int $userId): bool;

    /**
     * Check if the current user has access to the current business
     */
    public function hasCurrentAccess(): bool;

    /**
     * Get the user's role in the current business
     */
    public function getCurrentRole(): ?string;

    /**
     * Check if the current user is the owner of the current business
     */
    public function isOwner(): bool;

    /**
     * Check if the current user is an admin in the current business
     */
    public function isAdmin(): bool;

    /**
     * Check if the current user can perform an action in the current business
     */
    public function can(string $permission): bool;

    /**
     * Switch to a different business for the current user
     */
    public function switchBusiness(int $businessId): bool;

    /**
     * Get all businesses the current user has access to
     * 
     * @return array<BusinessData>
     */
    public function getAccessibleBusinesses(): array;

    /**
     * Get businesses owned by a specific user
     * 
     * @param int $userId
     * @return array<BusinessData>
     */
    public function getOwnedBusinesses(int $userId): array;

    /**
     * Get user's businesses with their role information
     * 
     * @param int $userId
     * @return array{business: BusinessData, role: string, isOwner: bool, status: string}[]
     */
    public function getUserBusinessesWithRoles(int $userId): array;

    /**
     * Get effective business for a user (current or first available)
     * 
     * @param int $userId
     * @return BusinessData|null
     */
    public function getEffectiveBusiness(int $userId): ?BusinessData;

    /**
     * Check if a user owns any business
     * 
     * @param int $userId
     * @return bool
     */
    public function ownsAnyBusiness(int $userId): bool;

    /**
     * Get a user's role in a specific business
     * 
     * @param int $userId
     * @param int $businessId
     * @return string|null
     */
    public function getUserRoleInBusiness(int $userId, int $businessId): ?string;
}