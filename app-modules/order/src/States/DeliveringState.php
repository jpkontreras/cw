<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Order is being delivered
 */
class DeliveringState extends OrderState
{
    public static $name = 'delivering';
    
    public function displayName(): string
    {
        return __('order.status.delivering');
    }
    
    public function color(): string
    {
        return 'blue';
    }
    
    public function icon(): string
    {
        return 'truck';
    }
    
    public function actionLabel(): string
    {
        return 'Start Delivery';
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
}