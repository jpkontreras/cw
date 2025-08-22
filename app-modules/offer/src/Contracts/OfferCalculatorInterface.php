<?php

declare(strict_types=1);

namespace Colame\Offer\Contracts;

use Colame\Offer\Data\OfferData;
use Colame\Offer\Data\DiscountCalculationData;

interface OfferCalculatorInterface
{
    public function calculate(OfferData $offer, array $orderData): DiscountCalculationData;
    
    public function calculatePercentageDiscount(float $percentage, float $amount, ?float $maxDiscount = null): float;
    
    public function calculateFixedDiscount(float $discountAmount, float $orderAmount): float;
    
    public function calculateBuyXGetY(array $items, int $buyQuantity, int $getQuantity, ?float $discountPercent = null): float;
    
    public function calculateComboDiscount(array $comboItems, array $orderItems, float $comboPrice): float;
    
    public function calculateTieredDiscount(float $orderAmount, array $tiers): float;
    
    public function compareOffers(array $offers, array $orderData): ?OfferData;
    
    public function canStackWith(OfferData $offer1, OfferData $offer2): bool;
    
    public function calculateStackedDiscount(array $offers, array $orderData): float;
}