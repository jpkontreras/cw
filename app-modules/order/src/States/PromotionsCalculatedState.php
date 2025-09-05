<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Promotions have been calculated for the order
 */
class PromotionsCalculatedState extends OrderState
{
    public static $name = 'promotions_calculated';
    
    public function displayName(): string
    {
        return __('order.status.promotions_calculated');
    }
    
    public function color(): string
    {
        return 'blue';
    }
    
    public function canBeModified(): bool
    {
        return true;
    }
    
    public function canBeCancelled(): bool
    {
        return true;
    }
    
    public function getNextPossibleStates(): array
    {
        return [PriceCalculatedState::class, CancelledState::class];
    }
}