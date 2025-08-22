<?php

declare(strict_types=1);

namespace Colame\Offer\Services;

use Colame\Offer\Contracts\OfferCalculatorInterface;
use Colame\Offer\Data\OfferData;
use Colame\Offer\Data\DiscountCalculationData;

class OfferCalculatorService implements OfferCalculatorInterface
{
    public function calculate(OfferData $offer, array $orderData): DiscountCalculationData
    {
        $originalAmount = $orderData['total_amount'] ?? 0;
        $discountAmount = 0;
        $affectedItems = [];
        $calculationDetails = [];
        $wasLimited = false;
        $limitReason = null;
        
        switch ($offer->type) {
            case 'percentage':
                $discountAmount = $this->calculatePercentageDiscount(
                    $offer->value,
                    $originalAmount,
                    $offer->maxDiscount
                );
                if ($offer->maxDiscount && $discountAmount >= $offer->maxDiscount) {
                    $wasLimited = true;
                    $limitReason = "Discount limited to maximum of \${$offer->maxDiscount}";
                }
                break;
                
            case 'fixed':
                $discountAmount = $this->calculateFixedDiscount(
                    $offer->value,
                    $originalAmount
                );
                break;
                
            case 'buy_x_get_y':
                $buyQty = $offer->conditions['buy_quantity'] ?? 0;
                $getQty = $offer->conditions['get_quantity'] ?? 0;
                $discountPercent = $offer->conditions['discount_percent'] ?? 100;
                
                $discountAmount = $this->calculateBuyXGetY(
                    $orderData['items'] ?? [],
                    $buyQty,
                    $getQty,
                    $discountPercent
                );
                $affectedItems = $this->getAffectedItemsForBuyXGetY(
                    $orderData['items'] ?? [],
                    $buyQty,
                    $getQty
                );
                break;
                
            case 'combo':
                $comboItems = $offer->conditions['combo_items'] ?? [];
                $comboPrice = $offer->value;
                
                $discountAmount = $this->calculateComboDiscount(
                    $comboItems,
                    $orderData['items'] ?? [],
                    $comboPrice
                );
                $affectedItems = $comboItems;
                break;
                
            case 'happy_hour':
            case 'early_bird':
                // Time-based percentage discount
                $discountAmount = $this->calculatePercentageDiscount(
                    $offer->value,
                    $originalAmount,
                    $offer->maxDiscount
                );
                break;
                
            case 'loyalty':
                // Loyalty discount based on customer tier or points
                $tierMultiplier = $orderData['customer_tier_multiplier'] ?? 1;
                $baseDiscount = $this->calculatePercentageDiscount(
                    $offer->value,
                    $originalAmount,
                    $offer->maxDiscount
                );
                $discountAmount = $baseDiscount * $tierMultiplier;
                break;
                
            case 'staff':
                // Staff discount
                $discountAmount = $this->calculatePercentageDiscount(
                    $offer->value,
                    $originalAmount,
                    $offer->maxDiscount
                );
                break;
        }
        
        $finalAmount = max(0, $originalAmount - $discountAmount);
        
        return new DiscountCalculationData(
            offerId: $offer->id,
            offerName: $offer->name,
            calculationType: $offer->type,
            originalAmount: $originalAmount,
            discountAmount: $discountAmount,
            finalAmount: $finalAmount,
            affectedItems: $affectedItems,
            calculationDetails: $calculationDetails,
            wasLimited: $wasLimited,
            limitReason: $limitReason,
        );
    }
    
    public function calculatePercentageDiscount(float $percentage, float $amount, ?float $maxDiscount = null): float
    {
        $discount = ($percentage / 100) * $amount;
        
        if ($maxDiscount !== null) {
            $discount = min($discount, $maxDiscount);
        }
        
        return round($discount, 2);
    }
    
    public function calculateFixedDiscount(float $discountAmount, float $orderAmount): float
    {
        return min($discountAmount, $orderAmount);
    }
    
    public function calculateBuyXGetY(array $items, int $buyQuantity, int $getQuantity, ?float $discountPercent = null): float
    {
        $discountPercent = $discountPercent ?? 100;
        $totalDiscount = 0;
        
        // Group items by ID and sort by price
        $itemGroups = $this->groupAndSortItems($items);
        
        foreach ($itemGroups as $itemGroup) {
            $totalQuantity = array_sum(array_column($itemGroup, 'quantity'));
            $setSize = $buyQuantity + $getQuantity;
            $numberOfSets = floor($totalQuantity / $setSize);
            
            if ($numberOfSets > 0) {
                // Get the prices of items that will be discounted
                $prices = array_column($itemGroup, 'price');
                sort($prices); // Sort ascending to discount cheapest items
                
                $discountedItems = array_slice($prices, 0, $getQuantity * $numberOfSets);
                $itemDiscount = array_sum($discountedItems) * ($discountPercent / 100);
                $totalDiscount += $itemDiscount;
            }
        }
        
        return round($totalDiscount, 2);
    }
    
    public function calculateComboDiscount(array $comboItems, array $orderItems, float $comboPrice): float
    {
        // Check if all combo items are present in the order
        $orderItemIds = array_column($orderItems, 'id');
        $hasAllItems = true;
        $comboOriginalPrice = 0;
        
        foreach ($comboItems as $comboItemId) {
            if (!in_array($comboItemId, $orderItemIds)) {
                $hasAllItems = false;
                break;
            }
            
            // Find the item in order and add its price
            foreach ($orderItems as $orderItem) {
                if ($orderItem['id'] == $comboItemId) {
                    $comboOriginalPrice += $orderItem['price'];
                    break;
                }
            }
        }
        
        if (!$hasAllItems) {
            return 0;
        }
        
        $discount = max(0, $comboOriginalPrice - $comboPrice);
        
        return round($discount, 2);
    }
    
    public function calculateTieredDiscount(float $orderAmount, array $tiers): float
    {
        $applicableDiscount = 0;
        
        // Sort tiers by minimum amount descending
        usort($tiers, function ($a, $b) {
            return $b['min_amount'] <=> $a['min_amount'];
        });
        
        // Find the applicable tier
        foreach ($tiers as $tier) {
            if ($orderAmount >= $tier['min_amount']) {
                $applicableDiscount = $tier['discount'];
                break;
            }
        }
        
        // Calculate discount based on type
        if (isset($tier['type']) && $tier['type'] === 'percentage') {
            return $this->calculatePercentageDiscount($applicableDiscount, $orderAmount);
        }
        
        return min($applicableDiscount, $orderAmount);
    }
    
    public function compareOffers(array $offers, array $orderData): ?OfferData
    {
        $bestOffer = null;
        $bestDiscount = 0;
        
        foreach ($offers as $offer) {
            if ($offer instanceof OfferData) {
                $calculation = $this->calculate($offer, $orderData);
                
                if ($calculation->discountAmount > $bestDiscount) {
                    $bestDiscount = $calculation->discountAmount;
                    $bestOffer = $offer;
                }
            }
        }
        
        return $bestOffer;
    }
    
    public function canStackWith(OfferData $offer1, OfferData $offer2): bool
    {
        // Both offers must be stackable
        if (!$offer1->isStackable || !$offer2->isStackable) {
            return false;
        }
        
        // Check if offers are mutually exclusive
        $exclusiveTypes = ['buy_x_get_y', 'combo'];
        if (in_array($offer1->type, $exclusiveTypes) && in_array($offer2->type, $exclusiveTypes)) {
            return false;
        }
        
        // Check if they target the same items (to avoid double discounting)
        if ($offer1->targetItemIds && $offer2->targetItemIds) {
            $intersection = array_intersect($offer1->targetItemIds, $offer2->targetItemIds);
            if (!empty($intersection)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function calculateStackedDiscount(array $offers, array $orderData): float
    {
        $totalDiscount = 0;
        $remainingAmount = $orderData['total_amount'] ?? 0;
        $appliedOffers = [];
        
        // Sort offers by priority
        usort($offers, function ($a, $b) {
            return ($b->priority ?? 0) <=> ($a->priority ?? 0);
        });
        
        foreach ($offers as $offer) {
            if (!$offer instanceof OfferData) {
                continue;
            }
            
            // Check if this offer can stack with already applied offers
            $canStack = true;
            foreach ($appliedOffers as $appliedOffer) {
                if (!$this->canStackWith($offer, $appliedOffer)) {
                    $canStack = false;
                    break;
                }
            }
            
            if ($canStack) {
                // Calculate discount on remaining amount
                $orderDataForCalculation = $orderData;
                $orderDataForCalculation['total_amount'] = $remainingAmount;
                
                $calculation = $this->calculate($offer, $orderDataForCalculation);
                $totalDiscount += $calculation->discountAmount;
                $remainingAmount -= $calculation->discountAmount;
                $appliedOffers[] = $offer;
            }
        }
        
        return round($totalDiscount, 2);
    }
    
    private function groupAndSortItems(array $items): array
    {
        $grouped = [];
        
        foreach ($items as $item) {
            $itemId = $item['id'] ?? $item['item_id'] ?? 0;
            if (!isset($grouped[$itemId])) {
                $grouped[$itemId] = [];
            }
            $grouped[$itemId][] = $item;
        }
        
        return $grouped;
    }
    
    private function getAffectedItemsForBuyXGetY(array $items, int $buyQuantity, int $getQuantity): array
    {
        $affected = [];
        $itemGroups = $this->groupAndSortItems($items);
        
        foreach ($itemGroups as $itemId => $itemGroup) {
            $totalQuantity = array_sum(array_column($itemGroup, 'quantity'));
            $setSize = $buyQuantity + $getQuantity;
            $numberOfSets = floor($totalQuantity / $setSize);
            
            if ($numberOfSets > 0) {
                $affected[] = [
                    'item_id' => $itemId,
                    'sets' => $numberOfSets,
                    'discounted_quantity' => $getQuantity * $numberOfSets,
                ];
            }
        }
        
        return $affected;
    }
}