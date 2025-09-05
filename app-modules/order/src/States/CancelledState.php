<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Order has been cancelled
 */
class CancelledState extends OrderState
{
    public static $name = 'cancelled';
    
    public function displayName(): string
    {
        return __('order.status.cancelled');
    }
    
    public function color(): string
    {
        return 'red';
    }
    
    public function canBeModified(): bool
    {
        return false;
    }
    
    public function canBeCancelled(): bool
    {
        return false; // Already cancelled
    }
    
    public function canProcessPayment(): bool
    {
        return false;
    }
    
    public function getNextPossibleStates(): array
    {
        return []; // Terminal state
    }
}