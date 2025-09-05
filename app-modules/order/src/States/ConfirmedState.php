<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Order has been confirmed and sent to kitchen
 */
class ConfirmedState extends OrderState
{
    public static $name = 'confirmed';
    
    public function displayName(): string
    {
        return __('order.status.confirmed');
    }
    
    public function color(): string
    {
        return 'green';
    }
    
    public function canBeModified(): bool
    {
        return true; // Still allow modifications but with restrictions
    }
    
    public function canBeCancelled(): bool
    {
        return true; // Can still cancel but may need approval
    }
    
    public function canProcessPayment(): bool
    {
        return true;
    }
    
    public function affectsKitchen(): bool
    {
        return true;
    }
    
    public function getNextPossibleStates(): array
    {
        return [PreparingState::class, CancelledState::class];
    }
}