<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Final price has been calculated
 */
class PriceCalculatedState extends OrderState
{
    public static $name = 'price_calculated';
    
    public function displayName(): string
    {
        return __('order.status.price_calculated');
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
    
    public function canProcessPayment(): bool
    {
        return true;
    }
    
    public function getNextPossibleStates(): array
    {
        return [ConfirmedState::class, CancelledState::class];
    }
}