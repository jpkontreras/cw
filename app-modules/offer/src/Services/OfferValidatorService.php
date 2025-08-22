<?php

declare(strict_types=1);

namespace Colame\Offer\Services;

use Colame\Offer\Contracts\OfferValidatorInterface;
use Colame\Offer\Data\OfferData;
use Colame\Offer\Data\ValidationResultData;
use Colame\Offer\Data\ValidationErrorData;
use Colame\Offer\Models\OfferUsage;
use Carbon\Carbon;

class OfferValidatorService implements OfferValidatorInterface
{
    public function validate(OfferData $offer, array $context = []): ValidationResultData
    {
        $errors = [];
        
        // Check if offer is active
        if (!$this->isActive($offer)) {
            $errors[] = new ValidationErrorData(
                field: 'isActive',
                message: 'Offer is not active',
                code: 'INACTIVE',
                context: ['status' => $offer->statusLabel],
            );
        }
        
        // Check date range
        if (!$this->isWithinDateRange($offer)) {
            $errors[] = new ValidationErrorData(
                field: 'dateRange',
                message: 'Offer is not within valid date range',
                code: 'DATE_RANGE',
                context: [
                    'starts_at' => $offer->startsAt?->toDateTimeString(),
                    'ends_at' => $offer->endsAt?->toDateTimeString(),
                ],
            );
        }
        
        // Check time range
        if (!$this->isWithinTimeRange($offer)) {
            $errors[] = new ValidationErrorData(
                field: 'timeRange',
                message: 'Offer is not within valid time range',
                code: 'TIME_RANGE',
                context: [
                    'valid_time_start' => $offer->validTimeStart,
                    'valid_time_end' => $offer->validTimeEnd,
                ],
            );
        }
        
        // Check day of week
        if (!$this->isValidForDayOfWeek($offer)) {
            $errors[] = new ValidationErrorData(
                field: 'dayOfWeek',
                message: 'Offer is not valid for today',
                code: 'DAY_OF_WEEK',
                context: [
                    'valid_days' => $offer->validDays,
                    'current_day' => strtolower(now()->format('l')),
                ],
            );
        }
        
        // Check minimum amount
        if (isset($context['total_amount']) && !$this->meetsMinimumAmount($offer, $context['total_amount'])) {
            $errors[] = new ValidationErrorData(
                field: 'minimumAmount',
                message: 'Order does not meet minimum amount requirement',
                code: 'MIN_AMOUNT',
                context: [
                    'required' => $offer->minimumAmount,
                    'actual' => $context['total_amount'],
                ],
            );
        }
        
        // Check usage limit
        if (!$this->hasUsageRemaining($offer)) {
            $errors[] = new ValidationErrorData(
                field: 'usageLimit',
                message: 'Offer has reached its usage limit',
                code: 'USAGE_LIMIT',
                context: [
                    'limit' => $offer->usageLimit,
                    'used' => $offer->usageCount,
                ],
            );
        }
        
        // Check customer usage limit
        if (isset($context['customer_id']) && !$this->hasCustomerUsageRemaining($offer, $context['customer_id'])) {
            $errors[] = new ValidationErrorData(
                field: 'customerUsage',
                message: 'Customer has reached usage limit for this offer',
                code: 'CUSTOMER_USAGE_LIMIT',
                context: ['customer_id' => $context['customer_id']],
            );
        }
        
        // Check location validity
        if (isset($context['location_id']) && !$this->isValidForLocation($offer, $context['location_id'])) {
            $errors[] = new ValidationErrorData(
                field: 'location',
                message: 'Offer is not valid for this location',
                code: 'INVALID_LOCATION',
                context: ['location_id' => $context['location_id']],
            );
        }
        
        // Check items validity
        if (isset($context['item_ids']) && !$this->isValidForItems($offer, $context['item_ids'])) {
            $errors[] = new ValidationErrorData(
                field: 'items',
                message: 'Offer is not valid for these items',
                code: 'INVALID_ITEMS',
                context: ['item_ids' => $context['item_ids']],
            );
        }
        
        // Check code if required
        if ($offer->code && isset($context['provided_code'])) {
            if (!$this->hasValidCode($offer, $context['provided_code'])) {
                $errors[] = new ValidationErrorData(
                    field: 'code',
                    message: 'Invalid offer code',
                    code: 'INVALID_CODE',
                    context: [],
                );
            }
        }
        
        // Check exclusions
        if (!$this->checkExclusions($offer, $context)) {
            $errors[] = new ValidationErrorData(
                field: 'exclusions',
                message: 'Order contains excluded items',
                code: 'EXCLUDED_ITEMS',
                context: ['excluded_items' => $offer->excludedItemIds],
            );
        }
        
        if (!empty($errors)) {
            return ValidationResultData::failure(
                reason: 'Offer validation failed',
                errors: $errors,
                suggestions: $this->getSuggestions($errors),
            );
        }
        
        return ValidationResultData::success($context);
    }
    
    public function isActive(OfferData $offer): bool
    {
        return $offer->isActive;
    }
    
    public function isWithinDateRange(OfferData $offer): bool
    {
        $now = Carbon::now();
        
        if ($offer->startsAt && $now->isBefore($offer->startsAt)) {
            return false;
        }
        
        if ($offer->endsAt && $now->isAfter($offer->endsAt)) {
            return false;
        }
        
        return true;
    }
    
    public function isWithinTimeRange(OfferData $offer): bool
    {
        if (!$offer->validTimeStart || !$offer->validTimeEnd) {
            return true;
        }
        
        $currentTime = now()->format('H:i');
        
        return $currentTime >= $offer->validTimeStart && $currentTime <= $offer->validTimeEnd;
    }
    
    public function meetsMinimumAmount(OfferData $offer, float $orderAmount): bool
    {
        if (!$offer->minimumAmount) {
            return true;
        }
        
        return $orderAmount >= $offer->minimumAmount;
    }
    
    public function hasUsageRemaining(OfferData $offer): bool
    {
        if (!$offer->usageLimit) {
            return true;
        }
        
        return $offer->usageCount < $offer->usageLimit;
    }
    
    public function hasCustomerUsageRemaining(OfferData $offer, int $customerId): bool
    {
        if (!$offer->usagePerCustomer) {
            return true;
        }
        
        $customerUsageCount = OfferUsage::where('offer_id', $offer->id)
            ->where('customer_id', $customerId)
            ->count();
        
        return $customerUsageCount < $offer->usagePerCustomer;
    }
    
    public function isValidForLocation(OfferData $offer, int $locationId): bool
    {
        if (!$offer->locationIds || empty($offer->locationIds)) {
            return true;
        }
        
        return in_array($locationId, $offer->locationIds);
    }
    
    public function isValidForItems(OfferData $offer, array $itemIds): bool
    {
        if (!$offer->targetItemIds || empty($offer->targetItemIds)) {
            return true;
        }
        
        // Check if at least one item matches
        $intersection = array_intersect($itemIds, $offer->targetItemIds);
        
        return !empty($intersection);
    }
    
    public function isValidForCategories(OfferData $offer, array $categoryIds): bool
    {
        if (!$offer->targetCategoryIds || empty($offer->targetCategoryIds)) {
            return true;
        }
        
        // Check if at least one category matches
        $intersection = array_intersect($categoryIds, $offer->targetCategoryIds);
        
        return !empty($intersection);
    }
    
    public function isValidForCustomerType(OfferData $offer, string $customerType): bool
    {
        if (!$offer->customerSegments || empty($offer->customerSegments)) {
            return true;
        }
        
        return in_array($customerType, $offer->customerSegments);
    }
    
    public function isValidForDayOfWeek(OfferData $offer, ?string $dayOfWeek = null): bool
    {
        if (!$offer->validDays || empty($offer->validDays)) {
            return true;
        }
        
        $day = $dayOfWeek ?: strtolower(now()->format('l'));
        
        return in_array($day, $offer->validDays);
    }
    
    public function hasValidCode(OfferData $offer, ?string $providedCode = null): bool
    {
        if (!$offer->code) {
            return true;
        }
        
        if (!$providedCode) {
            return false;
        }
        
        return strcasecmp($offer->code, $providedCode) === 0;
    }
    
    public function checkExclusions(OfferData $offer, array $orderData): bool
    {
        if (!$offer->excludedItemIds || empty($offer->excludedItemIds)) {
            return true;
        }
        
        if (!isset($orderData['item_ids']) || empty($orderData['item_ids'])) {
            return true;
        }
        
        // Check if any excluded items are in the order
        $intersection = array_intersect($orderData['item_ids'], $offer->excludedItemIds);
        
        return empty($intersection);
    }
    
    private function getSuggestions(array $errors): array
    {
        $suggestions = [];
        
        foreach ($errors as $error) {
            switch ($error->code) {
                case 'MIN_AMOUNT':
                    $suggestions[] = 'Add more items to meet the minimum amount requirement';
                    break;
                case 'INVALID_ITEMS':
                    $suggestions[] = 'This offer is only valid for specific items';
                    break;
                case 'INVALID_LOCATION':
                    $suggestions[] = 'This offer is not available at your location';
                    break;
                case 'TIME_RANGE':
                    $suggestions[] = 'This offer is only valid during specific hours';
                    break;
                case 'DAY_OF_WEEK':
                    $suggestions[] = 'This offer is only valid on certain days';
                    break;
                case 'CUSTOMER_USAGE_LIMIT':
                    $suggestions[] = 'You have already used this offer the maximum number of times';
                    break;
            }
        }
        
        return array_unique($suggestions);
    }
}