<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Order has been started and is being taken
 */
class StartedState extends OrderState
{
    public static $name = 'started';
    
    public function displayName(): string
    {
        return __('order.status.started');
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
    
    public function canAddItems(): bool
    {
        return true;
    }
    
    public function getNextPossibleStates(): array
    {
        return [ItemsAddedState::class, CancelledState::class];
    }
}