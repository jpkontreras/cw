<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Order is ready for pickup/serving
 */
class ReadyState extends OrderState
{
    public static $name = 'ready';
    
    public function displayName(): string
    {
        return __('order.status.ready');
    }
    
    public function color(): string
    {
        return 'green';
    }
    
    public function icon(): string
    {
        return 'package';
    }
    
    public function actionLabel(): string
    {
        return 'Mark Ready';
    }
    
    public function canBeModified(): bool
    {
        return false;
    }
    
    public function canBeCancelled(): bool
    {
        return false; // Too late to cancel
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