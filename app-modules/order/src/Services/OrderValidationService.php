<?php

declare(strict_types=1);

namespace Colame\Order\Services;

use Colame\Order\Exceptions\InvalidOrderException;
use Colame\Order\Models\Order;

/**
 * Service for order validation
 */
class OrderValidationService
{
    /**
     * Minimum order amount
     */
    private const MIN_ORDER_AMOUNT = 0.01;

    /**
     * Maximum items per order
     */
    private const MAX_ITEMS_PER_ORDER = 100;

    /**
     * Maximum quantity per item
     */
    private const MAX_QUANTITY_PER_ITEM = 999;

    /**
     * Validate order items
     */
    public function validateOrderItems(array $items): bool
    {
        if (empty($items)) {
            throw new InvalidOrderException('Order must contain at least one item');
        }

        if (count($items) > self::MAX_ITEMS_PER_ORDER) {
            throw new InvalidOrderException(
                sprintf('Order cannot contain more than %d items', self::MAX_ITEMS_PER_ORDER)
            );
        }

        foreach ($items as $item) {
            $this->validateOrderItem($item);
        }

        return true;
    }

    /**
     * Validate single order item
     */
    private function validateOrderItem($item): void
    {
        // Validate item ID
        if (!isset($item['itemId']) && !isset($item['item_id'])) {
            throw new InvalidOrderException('Item ID is required');
        }

        $itemId = $item['itemId'] ?? $item['item_id'];
        if (!is_numeric($itemId) || $itemId <= 0) {
            throw new InvalidOrderException('Invalid item ID');
        }

        // Validate quantity
        $quantity = $item['quantity'] ?? 1;
        if (!is_numeric($quantity) || $quantity <= 0) {
            throw new InvalidOrderException('Item quantity must be greater than 0');
        }

        if ($quantity > self::MAX_QUANTITY_PER_ITEM) {
            throw new InvalidOrderException(
                sprintf('Item quantity cannot exceed %d', self::MAX_QUANTITY_PER_ITEM)
            );
        }

        // Validate price
        $unitPrice = $item['unitPrice'] ?? $item['unit_price'] ?? 0;
        if (!is_numeric($unitPrice) || $unitPrice < 0) {
            throw new InvalidOrderException('Invalid unit price');
        }

        // Validate modifiers if present
        if (isset($item['modifiers']) && is_array($item['modifiers'])) {
            $this->validateModifiers($item['modifiers']);
        }
    }

    /**
     * Validate modifiers
     */
    private function validateModifiers(array $modifiers): void
    {
        foreach ($modifiers as $modifier) {
            if (!isset($modifier['id']) || !is_numeric($modifier['id'])) {
                throw new InvalidOrderException('Invalid modifier ID');
            }

            if (!isset($modifier['name']) || empty($modifier['name'])) {
                throw new InvalidOrderException('Modifier name is required');
            }

            if (!isset($modifier['price']) || !is_numeric($modifier['price'])) {
                throw new InvalidOrderException('Invalid modifier price');
            }
        }
    }

    /**
     * Validate order status transition
     */
    public function validateStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            Order::STATUS_DRAFT => [Order::STATUS_PLACED, Order::STATUS_CANCELLED],
            Order::STATUS_PLACED => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
            Order::STATUS_CONFIRMED => [Order::STATUS_PREPARING, Order::STATUS_CANCELLED],
            Order::STATUS_PREPARING => [Order::STATUS_READY],
            Order::STATUS_READY => [Order::STATUS_COMPLETED],
            Order::STATUS_COMPLETED => [],
            Order::STATUS_CANCELLED => [],
        ];

        if (!isset($validTransitions[$currentStatus])) {
            throw new InvalidOrderException("Invalid current status: {$currentStatus}");
        }

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new InvalidOrderException(
                "Cannot transition from '{$currentStatus}' to '{$newStatus}'"
            );
        }

        return true;
    }

    /**
     * Validate order amount
     */
    public function validateOrderAmount(float $amount): bool
    {
        if ($amount < self::MIN_ORDER_AMOUNT) {
            throw new InvalidOrderException(
                sprintf('Order amount must be at least %.2f', self::MIN_ORDER_AMOUNT)
            );
        }

        return true;
    }

    /**
     * Validate customer phone number
     */
    public function validatePhoneNumber(?string $phone): bool
    {
        if (!$phone) {
            return true; // Phone is optional
        }

        // Basic phone validation (digits, spaces, +, -, parentheses)
        if (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
            throw new InvalidOrderException('Invalid phone number format');
        }

        // Remove all non-digit characters for length check
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            throw new InvalidOrderException('Phone number must contain between 7 and 15 digits');
        }

        return true;
    }

    /**
     * Validate order for placement
     */
    public function validateForPlacement(array $orderData): bool
    {
        // Validate required fields
        if (!isset($orderData['user_id']) || !is_numeric($orderData['user_id'])) {
            throw new InvalidOrderException('Valid user ID is required');
        }

        if (!isset($orderData['location_id']) || !is_numeric($orderData['location_id'])) {
            throw new InvalidOrderException('Valid location ID is required');
        }

        // Validate items if present
        if (isset($orderData['items'])) {
            $this->validateOrderItems($orderData['items']);
        }

        // Validate phone if present
        if (isset($orderData['customer_phone'])) {
            $this->validatePhoneNumber($orderData['customer_phone']);
        }

        return true;
    }
}