<?php

namespace Colame\Item\Contracts;

use App\Core\Contracts\BaseRepositoryInterface;
use Colame\Item\Data\ItemLocationPriceData;
use Illuminate\Support\Collection;

interface PricingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a pricing rule by ID
     */
    public function find(int $id): ?ItemLocationPriceData;
    
    /**
     * Get all pricing rules for an item
     */
    public function getPricingRulesForItem(int $itemId, ?int $variantId = null): Collection;
    
    /**
     * Get pricing rules for an item at a specific location
     */
    public function getPricingForItemAtLocation(int $itemId, int $locationId, ?int $variantId = null): Collection;
    
    /**
     * Get the current applicable price for an item
     */
    public function getCurrentPrice(int $itemId, int $locationId, ?int $variantId = null, ?\DateTime $dateTime = null): ?ItemLocationPriceData;
    
    /**
     * Get active pricing rules for a location
     */
    public function getActivePricingForLocation(int $locationId): Collection;
    
    /**
     * Create a new pricing rule
     */
    public function create(array $data): ItemLocationPriceData;
    
    /**
     * Update a pricing rule
     */
    public function update(int $id, array $data): ItemLocationPriceData;
    
    /**
     * Create bulk pricing rules
     */
    public function createBulk(array $rules): Collection;
    
    /**
     * Check for pricing conflicts
     */
    public function checkConflicts(array $data): Collection;
    
    /**
     * Activate a pricing rule
     */
    public function activate(int $id): bool;
    
    /**
     * Deactivate a pricing rule
     */
    public function deactivate(int $id): bool;
    
    /**
     * Delete a pricing rule
     */
    public function delete(int $id): bool;
    
    /**
     * Delete expired pricing rules
     */
    public function deleteExpired(): int;
    
    /**
     * Get pricing rules expiring soon
     */
    public function getExpiringSoon(int $days = 7): Collection;
    
    /**
     * Clone pricing rules from one location to another
     */
    public function clonePricingToLocation(int $fromLocationId, int $toLocationId, array $itemIds = []): Collection;
    
    /**
     * Apply percentage adjustment to prices
     */
    public function applyPercentageAdjustment(array $itemIds, int $locationId, float $percentage): int;
}