<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Order has been completed and delivered/served
 */
class CompletedState extends OrderState
{
    public static $name = 'completed';
    
    public function displayName(): string
    {
        return __('order.status.completed');
    }
    
    public function color(): string
    {
        return 'green';
    }
    
    public function icon(): string
    {
        return 'check-circle';
    }
    
    public function actionLabel(): string
    {
        return 'Complete Order';
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
        return true; // Can still process payment if not fully paid
    }
}