<?php

declare(strict_types=1);

namespace Colame\Offer\Contracts;

use Colame\Offer\Data\OfferData;
use Colame\Offer\Data\ValidationResultData;

interface OfferValidatorInterface
{
    public function validate(OfferData $offer, array $context = []): ValidationResultData;
    
    public function isActive(OfferData $offer): bool;
    
    public function isWithinDateRange(OfferData $offer): bool;
    
    public function isWithinTimeRange(OfferData $offer): bool;
    
    public function meetsMinimumAmount(OfferData $offer, float $orderAmount): bool;
    
    public function hasUsageRemaining(OfferData $offer): bool;
    
    public function hasCustomerUsageRemaining(OfferData $offer, int $customerId): bool;
    
    public function isValidForLocation(OfferData $offer, int $locationId): bool;
    
    public function isValidForItems(OfferData $offer, array $itemIds): bool;
    
    public function isValidForCategories(OfferData $offer, array $categoryIds): bool;
    
    public function isValidForCustomerType(OfferData $offer, string $customerType): bool;
    
    public function isValidForDayOfWeek(OfferData $offer, ?string $dayOfWeek = null): bool;
    
    public function hasValidCode(OfferData $offer, ?string $providedCode = null): bool;
    
    public function checkExclusions(OfferData $offer, array $orderData): bool;
}