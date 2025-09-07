<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Order has been refunded
 */
class RefundedState extends OrderState
{
    public static $name = 'refunded';
    
    public function displayName(): string
    {
        return __('order.status.refunded');
    }
    
    public function color(): string
    {
        return 'purple';
    }
    
    public function icon(): string
    {
        return 'rotate-ccw';
    }
    
    public function actionLabel(): string
    {
        return 'Refund Order';
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
        return false;
    }
}