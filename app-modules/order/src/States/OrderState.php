<?php

declare(strict_types=1);

namespace Colame\Order\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;
use Colame\Order\Models\Order;

/**
 * Base abstract class for order states
 */
abstract class OrderState extends State
{
    /**
     * Get the display name for this state
     */
    abstract public function displayName(): string;
    
    /**
     * Get the color/badge class for UI display
     */
    abstract public function color(): string;
    
    /**
     * Check if the order can be modified in this state
     */
    public function canBeModified(): bool
    {
        return false;
    }
    
    /**
     * Check if the order can be cancelled in this state
     */
    public function canBeCancelled(): bool
    {
        return false;
    }
    
    /**
     * Check if items can be added in this state
     */
    public function canAddItems(): bool
    {
        return false;
    }
    
    /**
     * Check if payment can be processed in this state
     */
    public function canProcessPayment(): bool
    {
        return false;
    }
    
    /**
     * Check if the order affects kitchen in this state
     */
    public function affectsKitchen(): bool
    {
        return false;
    }
    
    /**
     * Get the next possible states
     */
    public function getNextPossibleStates(): array
    {
        return [];
    }
    
    /**
     * Configure the state
     */
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(DraftState::class)
            ->allowTransition(DraftState::class, StartedState::class)
            ->allowTransition(StartedState::class, ItemsAddedState::class)
            ->allowTransition(ItemsAddedState::class, ItemsValidatedState::class)
            ->allowTransition(ItemsValidatedState::class, PromotionsCalculatedState::class)
            ->allowTransition(PromotionsCalculatedState::class, PriceCalculatedState::class)
            ->allowTransition(PriceCalculatedState::class, ConfirmedState::class)
            ->allowTransition(ConfirmedState::class, PreparingState::class)
            ->allowTransition(PreparingState::class, ReadyState::class)
            ->allowTransition(ReadyState::class, DeliveringState::class)
            ->allowTransition(ReadyState::class, CompletedState::class)
            ->allowTransition(DeliveringState::class, DeliveredState::class)
            ->allowTransition(DeliveredState::class, CompletedState::class)
            
            // Cancellation transitions
            ->allowTransition(DraftState::class, CancelledState::class)
            ->allowTransition(StartedState::class, CancelledState::class)
            ->allowTransition(ItemsAddedState::class, CancelledState::class)
            ->allowTransition(ItemsValidatedState::class, CancelledState::class)
            ->allowTransition(PromotionsCalculatedState::class, CancelledState::class)
            ->allowTransition(PriceCalculatedState::class, CancelledState::class)
            ->allowTransition(ConfirmedState::class, CancelledState::class)
            ->allowTransition(PreparingState::class, CancelledState::class)
            
            // Refund transition
            ->allowTransition(CompletedState::class, RefundedState::class);
    }
}