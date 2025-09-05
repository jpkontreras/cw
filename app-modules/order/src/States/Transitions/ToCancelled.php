<?php

declare(strict_types=1);

namespace Colame\Order\States\Transitions;

use Spatie\ModelStates\Transition;
use Colame\Order\Models\Order;
use Colame\Order\States\CancelledState;
use Colame\Order\Aggregates\OrderAggregate;

/**
 * Transition to cancelled state
 */
class ToCancelled extends Transition
{
    private Order $order;
    private string $reason;
    private ?string $cancelledBy;
    
    public function __construct(Order $order, string $reason = '', ?string $cancelledBy = null)
    {
        $this->order = $order;
        $this->reason = $reason;
        $this->cancelledBy = $cancelledBy ?? request()->user()?->email ?? 'system';
    }
    
    public function handle(): Order
    {
        // Update the state
        $this->order->state->transitionTo(CancelledState::class);
        
        // Record event in event sourcing
        if ($this->order->uuid) {
            $aggregate = OrderAggregate::retrieve($this->order->uuid);
            $aggregate->cancel($this->reason, $this->cancelledBy);
            $aggregate->persist();
        }
        
        // Update order fields
        $this->order->cancellation_reason = $this->reason;
        $this->order->cancelled_at = now();
        $this->order->save();
        
        // Notify relevant parties
        if ($this->order->state->was(PreparingState::class)) {
            // Notify kitchen to stop preparation
            event(new \Colame\Order\Events\OrderCancelledInKitchen(
                orderId: $this->order->id,
                reason: $this->reason
            ));
        }
        
        return $this->order;
    }
    
    public function canTransition(): bool
    {
        // Cannot cancel if already completed or cancelled
        return !in_array($this->order->state::class, [
            CompletedState::class,
            CancelledState::class,
            RefundedState::class
        ]);
    }
}