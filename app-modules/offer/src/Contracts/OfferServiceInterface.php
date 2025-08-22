<?php

declare(strict_types=1);

namespace Colame\Offer\Contracts;

use Colame\Offer\Data\OfferData;
use Colame\Offer\Data\AppliedOfferData;
use Spatie\LaravelData\DataCollection;

interface OfferServiceInterface
{
    public function createOffer(array $data): OfferData;
    
    public function updateOffer(int $id, array $data): OfferData;
    
    public function deleteOffer(int $id): bool;
    
    public function duplicateOffer(int $id, ?array $overrides = null): OfferData;
    
    public function getAvailableOffersForOrder(array $orderData): DataCollection;
    
    public function applyOfferToOrder(int $offerId, array $orderData): AppliedOfferData;
    
    public function applyBestOfferToOrder(array $orderData): ?AppliedOfferData;
    
    public function validateOfferForOrder(int $offerId, array $orderData): bool;
    
    public function calculateDiscount(OfferData $offer, array $orderData): float;
    
    public function recordUsage(int $offerId, ?int $customerId = null, float $discountAmount = 0): bool;
    
    public function getOfferAnalytics(int $offerId, ?string $startDate = null, ?string $endDate = null): array;
    
    public function bulkActivate(array $offerIds): int;
    
    public function bulkDeactivate(array $offerIds): int;
}