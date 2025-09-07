<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Kitchen is preparing the order
 */
class PreparingState extends OrderState
{
    public static $name = 'preparing';
    
    public function displayName(): string
    {
        return __('order.status.preparing');
    }
    
    public function color(): string
    {
        return 'yellow';
    }
    
    public function icon(): string
    {
        return 'clock';
    }
    
    public function actionLabel(): string
    {
        return 'Start Preparing';
    }
    
    public function canBeModified(): bool
    {
        return false; // No modifications while preparing
    }
    
    public function canBeCancelled(): bool
    {
        return true; // Can cancel but may incur charges
    }
    
    public function canProcessPayment(): bool
    {
        return true;
    }
    
    public function affectsKitchen(): bool
    {
        return true;
    }
}