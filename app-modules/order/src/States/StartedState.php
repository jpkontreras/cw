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
    
    public function icon(): string
    {
        return 'play-circle';
    }
    
    public function actionLabel(): string
    {
        return 'Start Order';
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
}