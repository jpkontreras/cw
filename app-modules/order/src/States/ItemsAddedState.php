<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Items have been added to the order
 */
class ItemsAddedState extends OrderState
{
    public static $name = 'items_added';
    
    public function displayName(): string
    {
        return __('order.status.items_added');
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
        return [ItemsValidatedState::class, CancelledState::class];
    }
}