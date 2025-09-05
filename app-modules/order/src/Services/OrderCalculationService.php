<?php

declare(strict_types=1);

namespace Colame\Order\Services;

use Colame\Order\Contracts\OrderItemRepositoryInterface;
use Colame\Order\Contracts\OrderRepositoryInterface;

/**
 * Service for order calculations
 */
class OrderCalculationService
{
    /**
     * Default tax rate (10%)
     */
    private const DEFAULT_TAX_RATE = 0.10;

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * Calculate order totals
     * All amounts are in minor units (cents, fils, etc.)
     */
    public function calculateOrderTotals(int $orderId): array
    {
        $items = $this->itemRepository->getByOrder($orderId);
        
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item->totalPrice; // Already in minor units
        }

        // Calculate tax (this would come from location/tax service in real implementation)
        $taxAmount = (int)($subtotal * self::DEFAULT_TAX_RATE);

        // Get discount amount (would come from applied offers)
        $discountAmount = 0;

        // Calculate total
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax' => $taxAmount,
            'discount' => $discountAmount,
            'total' => $totalAmount,
        ];
    }

    /**
     * Calculate item price with modifiers
     * @param int $basePrice Base price in minor units
     * @param array $modifiers Array of modifiers with 'price' key in minor units
     * @return int Total price in minor units
     */
    public function calculateItemPrice(int $basePrice, array $modifiers = []): int
    {
        $modifiersTotal = 0;
        
        foreach ($modifiers as $modifier) {
            $modifiersTotal += (int)($modifier['price'] ?? 0);
        }

        return $basePrice + $modifiersTotal;
    }

    /**
     * Apply discount to order
     * @param int $totalAmount Total amount in minor units
     * @param float $discountPercentage Discount percentage (0-100)
     * @return int Discounted amount in minor units
     */
    public function applyDiscount(int $totalAmount, float $discountPercentage): int
    {
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and 100');
        }

        return (int)($totalAmount * (1 - $discountPercentage / 100));
    }

    /**
     * Calculate estimated preparation time in minutes
     */
    public function calculateEstimatedPrepTime(array $items): int
    {
        // Base preparation time
        $basePrepTime = 10;
        
        // Add time based on number of items
        $itemPrepTime = count($items) * 3;
        
        // Add time for complex items (would check item properties in real implementation)
        $complexityTime = 0;

        return $basePrepTime + $itemPrepTime + $complexityTime;
    }

    /**
     * Calculate order commission for delivery platforms
     */
    public function calculateCommission(float $totalAmount, float $commissionRate): float
    {
        return round($totalAmount * $commissionRate, 2);
    }

    /**
     * Calculate tip amount
     */
    public function calculateTip(float $subtotal, float $tipPercentage): float
    {
        if ($tipPercentage < 0) {
            throw new \InvalidArgumentException('Tip percentage cannot be negative');
        }

        return round($subtotal * $tipPercentage / 100, 2);
    }

    /**
     * Get order summary
     */
    public function getOrderSummary(int $orderId): array
    {
        $order = $this->orderRepository->find($orderId);
        $items = $this->itemRepository->getByOrder($orderId);
        
        return [
            'itemCount' => count($items),
            'totalQuantity' => array_sum(array_map(fn($item) => $item->quantity, $items)),
            'subtotal' => $order->subtotal,
            'tax' => $order->tax,
            'discount' => $order->discount,
            'total' => $order->total,
            'estimatedPrepTime' => $this->calculateEstimatedPrepTime($items),
        ];
    }
}