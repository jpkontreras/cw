<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Colame\Order\Contracts\OrderEventInterface;
use Colame\Order\Data\OrderData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an order is created
 */
class OrderCreated implements OrderEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private \DateTimeInterface $timestamp;

    public function __construct(
        private OrderData $order
    ) {
        $this->timestamp = new \DateTime();
    }

    /**
     * Get the order ID
     */
    public function getOrderId(): int
    {
        return $this->order->id;
    }

    /**
     * Get order data as array
     */
    public function getOrderData(): array
    {
        return $this->order->toArray();
    }

    /**
     * Get the event type
     */
    public function getEventType(): string
    {
        return 'order.created';
    }

    /**
     * Get item IDs in the order
     */
    public function getItemIds(): array
    {
        if (!$this->order->items) {
            return [];
        }

        return $this->order->items->map(fn($item) => $item->itemId)->toArray();
    }

    /**
     * Get the location ID
     */
    public function getLocationId(): ?int
    {
        return $this->order->locationId;
    }

    /**
     * Get the user ID
     */
    public function getUserId(): ?int
    {
        return $this->order->userId;
    }

    /**
     * Get the total amount
     */
    public function getTotalAmount(): float
    {
        return $this->order->totalAmount;
    }

    /**
     * Get event timestamp
     */
    public function getTimestamp(): \DateTimeInterface
    {
        return $this->timestamp;
    }

    /**
     * Get additional context data
     */
    public function getContext(): array
    {
        return [
            'status' => $this->order->status,
            'customer_name' => $this->order->customerName,
            'item_count' => $this->order->items ? $this->order->items->count() : 0,
        ];
    }

    /**
     * Get the order data
     */
    public function getOrder(): OrderData
    {
        return $this->order;
    }
}