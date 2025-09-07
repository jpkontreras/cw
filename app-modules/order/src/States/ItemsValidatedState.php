<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Items have been validated for availability
 */
class ItemsValidatedState extends OrderState
{
    public static $name = 'items_validated';
    
    public function displayName(): string
    {
        return __('order.status.items_validated');
    }
    
    public function color(): string
    {
        return 'blue';
    }
    
    public function icon(): string
    {
        return 'check-square';
    }
    
    public function actionLabel(): string
    {
        return 'Validate Items';
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
        return false; // Need to go back to add more items
    }
}