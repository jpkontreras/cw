<?php

declare(strict_types=1);

namespace Colame\Order\Contracts;

/**
 * Order event interface for cross-module communication
 */
interface OrderEventInterface
{
    /**
     * Get the order ID
     */
    public function getOrderId(): int;

    /**
     * Get order data as array
     */
    public function getOrderData(): array;

    /**
     * Get the event type
     */
    public function getEventType(): string;

    /**
     * Get item IDs in the order
     */
    public function getItemIds(): array;

    /**
     * Get the location ID
     */
    public function getLocationId(): ?int;

    /**
     * Get the user ID
     */
    public function getUserId(): ?int;

    /**
     * Get the total amount
     */
    public function getTotalAmount(): float;

    /**
     * Get event timestamp
     */
    public function getTimestamp(): \DateTimeInterface;

    /**
     * Get additional context data
     */
    public function getContext(): array;
}