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
    
    public function icon(): string
    {
        return 'check-circle-2';
    }
    
    public function actionLabel(): string
    {
        return 'Mark Delivered';
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