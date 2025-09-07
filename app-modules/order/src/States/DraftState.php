<?php

declare(strict_types=1);

namespace Colame\Order\States;

/**
 * Initial draft state when order is created but not started
 */
class DraftState extends OrderState
{
    public static $name = 'draft';
    
    public function displayName(): string
    {
        return __('order.status.draft');
    }
    
    public function color(): string
    {
        return 'gray';
    }
    
    public function icon(): string
    {
        return 'file-text';
    }
    
    public function actionLabel(): string
    {
        return 'Create Draft';
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