<?php

namespace Colame\Item\Services;

use App\Core\Services\BaseService;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Contracts\PricingRepositoryInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Data\ItemLocationPriceData;
use Colame\Item\Data\PriceCalculationData;
use Colame\Item\Exceptions\ItemNotFoundException;
use Colame\Item\Exceptions\InvalidPricingRuleException;
use Colame\Item\Events\PriceUpdated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PricingService extends BaseService
{
    private const CACHE_TTL = 3600; // 1 hour
    
    public function __construct(
        private readonly PricingRepositoryInterface $pricingRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        FeatureFlagInterface $features,
    ) {
        parent::__construct($features);
    }
    
    /**
     * Calculate price for an item with all applicable rules
     */
    public function calculatePrice(
        int $itemId,
        ?int $variantId = null,
        ?int $locationId = null,
        ?array $modifierIds = null,
        ?Carbon $datetime = null
    ): PriceCalculationData {
        $datetime = $datetime ?? now();
        
        // Check cache if enabled
        if ($this->features->isEnabled('item.price_caching')) {
            $cacheKey = $this->buildPriceCacheKey($itemId, $variantId, $locationId, $modifierIds, $datetime);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }
        
        // Get base item
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        // Start with base price (default to 0 if null)
        $originalBasePrice = $item->basePrice ?? 0;
        $basePrice = $originalBasePrice;
        $variantAdjustment = 0;
        $modifierTotal = 0;
        $appliedRules = [];
        
        // Apply variant price if specified
        if ($variantId) {
            $variant = $this->itemRepository->findVariant($variantId);
            if ($variant && $variant->itemId == $itemId) {
                // Variants have price adjustments, not absolute prices
                $variantAdjustment = $variant->priceAdjustment ?? 0;
                $basePrice += $variantAdjustment;
            }
        }
        
        // Apply location-specific pricing
        if ($locationId && $this->features->isEnabled('item.location_pricing')) {
            $locationPrice = $this->getLocationPrice($itemId, $variantId, $locationId, $datetime);
            if ($locationPrice) {
                $basePrice = $this->applyPriceRule($basePrice, $locationPrice);
                $appliedRules[] = [
                    'type' => 'location',
                    'name' => $locationPrice->name,
                    'adjustment' => $locationPrice->priceType == 'fixed' 
                        ? $locationPrice->priceValue - $basePrice
                        : $basePrice * ($locationPrice->priceValue / 100)
                ];
            }
        }
        
        // Apply time-based pricing
        if ($this->features->isEnabled('item.time_based_pricing')) {
            $timeRules = $this->getTimeBasedRules($itemId, $locationId, $datetime);
            foreach ($timeRules as $rule) {
                $basePrice = $this->applyPriceRule($basePrice, $rule);
                $appliedRules[] = [
                    'type' => 'time_based',
                    'name' => $rule->name,
                    'adjustment' => $this->calculateRuleAdjustment($basePrice, $rule)
                ];
            }
        }
        
        // Calculate modifier adjustments
        if (!empty($modifierIds)) {
            $modifiers = $this->pricingRepository->getModifierPrices($modifierIds, $locationId);
            foreach ($modifiers as $modifier) {
                $modifierTotal += $modifier->priceAdjustment;
            }
        }
        
        $finalPrice = max(0, $basePrice + $modifierTotal);
        
        // Apply global discount if any
        if ($this->features->isEnabled('item.global_discounts')) {
            $discount = $this->getGlobalDiscount($itemId, $locationId, $datetime);
            if ($discount) {
                $discountAmount = $this->calculateDiscountAmount($finalPrice, $discount);
                $finalPrice -= $discountAmount;
                $appliedRules[] = [
                    'type' => 'discount',
                    'name' => $discount->name,
                    'adjustment' => -$discountAmount
                ];
            }
        }
        
        $result = new PriceCalculationData(
            itemId: $itemId,
            variantId: $variantId,
            locationId: $locationId,
            basePrice: $originalBasePrice,
            variantAdjustment: $variantAdjustment,
            modifierAdjustments: [], // TODO: This should be populated with modifier details
            locationPrice: null, // TODO: This should be set if location pricing is applied
            subtotal: $originalBasePrice + $variantAdjustment + $modifierTotal,
            total: $finalPrice,
            currency: $this->getCurrency($locationId),
            appliedRules: $appliedRules
        );
        
        // Cache result if enabled
        if ($this->features->isEnabled('item.price_caching')) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }
        
        return $result;
    }
    
    /**
     * Set location-specific price for an item
     */
    public function setLocationPrice(array $data): ItemLocationPriceData
    {
        $this->authorize('item.pricing.manage');
        
        // Validate item exists
        $item = $this->itemRepository->find($data['item_id']);
        if (!$item) {
            throw new ItemNotFoundException($data['item_id']);
        }
        
        // Validate variant if specified
        if (!empty($data['variant_id'])) {
            $variant = $this->itemRepository->findVariant($data['variant_id']);
            if (!$variant || $variant->itemId != $data['item_id']) {
                throw new InvalidPricingRuleException('Invalid variant for item');
            }
        }
        
        // Validate price rules
        $this->validatePriceRule($data);
        
        DB::beginTransaction();
        try {
            $locationPrice = $this->pricingRepository->setLocationPrice($data);
            
            // Clear cache for this item/location
            $this->clearPriceCache($data['item_id'], $data['variant_id'] ?? null, $data['location_id']);
            
            DB::commit();
            
            event(new PriceUpdated($locationPrice));
            
            return $locationPrice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to set location price', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update location price
     */
    public function updateLocationPrice(int $priceId, array $data): ItemLocationPriceData
    {
        $this->authorize('item.pricing.manage');
        
        $price = $this->pricingRepository->findLocationPrice($priceId);
        if (!$price) {
            throw new InvalidPricingRuleException('Location price not found');
        }
        
        // Validate price rules
        $this->validatePriceRule($data);
        
        DB::beginTransaction();
        try {
            $updatedPrice = $this->pricingRepository->updateLocationPrice($priceId, $data);
            
            // Clear cache
            $this->clearPriceCache($price->itemId, $price->variantId, $price->locationId);
            
            DB::commit();
            
            event(new PriceUpdated($updatedPrice));
            
            return $updatedPrice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update location price', [
                'price_id' => $priceId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Delete location price
     */
    public function deleteLocationPrice(int $priceId): bool
    {
        $this->authorize('item.pricing.manage');
        
        $price = $this->pricingRepository->findLocationPrice($priceId);
        if (!$price) {
            throw new InvalidPricingRuleException('Location price not found');
        }
        
        DB::beginTransaction();
        try {
            $deleted = $this->pricingRepository->deleteLocationPrice($priceId);
            
            // Clear cache
            $this->clearPriceCache($price->itemId, $price->variantId, $price->locationId);
            
            DB::commit();
            
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete location price', [
                'price_id' => $priceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get price history for an item
     */
    public function getPriceHistory(
        int $itemId,
        ?int $locationId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        return $this->pricingRepository->getPriceHistory($itemId, $locationId, $startDate, $endDate);
    }
    
    /**
     * Bulk update prices
     */
    public function bulkUpdatePrices(array $updates): int
    {
        $this->authorize('item.pricing.bulk_manage');
        
        $updated = 0;
        
        DB::beginTransaction();
        try {
            foreach ($updates as $update) {
                $this->validatePriceRule($update);
                
                if (isset($update['id'])) {
                    $this->updateLocationPrice($update['id'], $update);
                } else {
                    $this->setLocationPrice($update);
                }
                $updated++;
            }
            
            DB::commit();
            
            // Clear all price caches
            Cache::tags(['item_prices'])->flush();
            
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk update prices', [
                'updates' => $updates,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get active pricing rules for location
     */
    public function getActiveRulesForLocation(int $locationId, ?Carbon $datetime = null): Collection
    {
        // The repository method already filters by current validity
        return $this->pricingRepository->getActivePricingForLocation($locationId);
    }
    
    /**
     * Validate pricing rule data
     */
    private function validatePriceRule(array $data): void
    {
        if (isset($data['price_type'])) {
            if (!in_array($data['price_type'], ['fixed', 'percentage'])) {
                throw new InvalidPricingRuleException('Invalid price type');
            }
            
            if ($data['price_type'] == 'percentage' && ($data['price_value'] < -100 || $data['price_value'] > 200)) {
                throw new InvalidPricingRuleException('Percentage must be between -100 and 200');
            }
        }
        
        if (isset($data['starts_at']) && isset($data['ends_at'])) {
            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = Carbon::parse($data['ends_at']);
            
            if ($endsAt->lte($startsAt)) {
                throw new InvalidPricingRuleException('End date must be after start date');
            }
        }
    }
    
    /**
     * Get location-specific price
     */
    private function getLocationPrice(
        int $itemId,
        ?int $variantId,
        int $locationId,
        Carbon $datetime
    ): ?ItemLocationPriceData {
        return $this->pricingRepository->getLocationPrice($itemId, $variantId, $locationId, $datetime);
    }
    
    /**
     * Get time-based pricing rules
     */
    private function getTimeBasedRules(
        int $itemId,
        ?int $locationId,
        Carbon $datetime
    ): Collection {
        $dayOfWeek = $datetime->dayOfWeek;
        $time = $datetime->format('H:i:s');
        
        return $this->pricingRepository->getTimeBasedRules($itemId, $locationId, $dayOfWeek, $time);
    }
    
    /**
     * Apply price rule to base price
     */
    private function applyPriceRule(float $basePrice, ItemLocationPriceData $rule): float
    {
        if ($rule->priceType == 'fixed') {
            return $rule->priceValue;
        }
        
        // Percentage adjustment
        return $basePrice * (1 + $rule->priceValue / 100);
    }
    
    /**
     * Calculate rule adjustment amount
     */
    private function calculateRuleAdjustment(float $basePrice, ItemLocationPriceData $rule): float
    {
        if ($rule->priceType == 'fixed') {
            return $rule->priceValue - $basePrice;
        }
        
        return $basePrice * ($rule->priceValue / 100);
    }
    
    /**
     * Get global discount if any
     */
    private function getGlobalDiscount(int $itemId, ?int $locationId, Carbon $datetime): ?object
    {
        // This would integrate with a promotions/offers module
        return null;
    }
    
    /**
     * Calculate discount amount
     */
    private function calculateDiscountAmount(float $price, object $discount): float
    {
        if ($discount->type == 'fixed') {
            return min($price, $discount->value);
        }
        
        return $price * ($discount->value / 100);
    }
    
    /**
     * Get currency for location
     */
    private function getCurrency(?int $locationId): string
    {
        // Default to system currency, would integrate with location module
        return config('item.default_currency', 'CLP');
    }
    
    /**
     * Build cache key for price calculation
     */
    private function buildPriceCacheKey(
        int $itemId,
        ?int $variantId,
        ?int $locationId,
        ?array $modifierIds,
        Carbon $datetime
    ): string {
        $parts = [
            'item_price',
            $itemId,
            $variantId ?? 'no_variant',
            $locationId ?? 'no_location',
            $datetime->format('Y-m-d-H'),
        ];
        
        if (!empty($modifierIds)) {
            sort($modifierIds);
            $parts[] = implode('-', $modifierIds);
        }
        
        return implode(':', $parts);
    }
    
    /**
     * Clear price cache for item
     */
    private function clearPriceCache(int $itemId, ?int $variantId, ?int $locationId): void
    {
        $pattern = "item_price:{$itemId}:*";
        
        if ($variantId) {
            $pattern = "item_price:{$itemId}:{$variantId}:*";
        }
        
        if ($locationId) {
            $pattern = "item_price:{$itemId}:*:{$locationId}:*";
        }
        
        $keys = Cache::getRedis()->keys($pattern);
        if (!empty($keys)) {
            Cache::deleteMultiple($keys);
        }
    }
}