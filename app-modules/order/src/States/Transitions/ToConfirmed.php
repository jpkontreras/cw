<?php

declare(strict_types=1);

namespace Colame\Order\States\Transitions;

use Spatie\ModelStates\Transition;
use Colame\Order\Models\Order;
use Colame\Order\States\ConfirmedState;
use Colame\Order\Aggregates\OrderAggregate;

/**
 * Transition to confirmed state
 */
class ToConfirmed extends Transition
{
    private Order $order;
    
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    
    public function handle(): Order
    {
        // Update the state
        $this->order->status->transitionTo(ConfirmedState::class);
        
        // Record event in event sourcing
        if ($this->order->uuid) {
            $aggregate = OrderAggregate::retrieve($this->order->uuid);
            $aggregate->confirmOrder();
            $aggregate->persist();
        }
        
        // Update timestamps
        $this->order->confirmed_at = now();
        $this->order->save();
        
        // Notify kitchen
        event(new \Colame\Order\Events\OrderConfirmedForKitchen(
            orderId: $this->order->id,
            locationId: $this->order->location_id,
            items: $this->order->items->toArray()
        ));
        
        return $this->order;
    }
    
    public function canTransition(): bool
    {
        // Check if all required fields are filled
        if (!$this->order->items || $this->order->items->isEmpty()) {
            return false;
        }
        
        // Check if price has been calculated
        if ($this->order->total <= 0) {
            return false;
        }
        
        return true;
    }
}