<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Order has been delivered
 */
class DeliveredState extends OrderState
{
    public static $name = 'delivered';
    
    public function displayName(): string
    {
        return __('order.status.delivered');
    }
    
    public function color(): string
    {
        return 'green';
    }
    
    public function canBeModified(): bool
    {
        return false;
    }
    
    public function canBeCancelled(): bool
    {
        return false;
    }
    
    public function canProcessPayment(): bool
    {
        return true;
    }
    
    public function getNextPossibleStates(): array
    {
        return [CompletedState::class];
    }
}