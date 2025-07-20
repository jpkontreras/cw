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
     */
    public function calculateOrderTotals(int $orderId): array
    {
        $items = $this->itemRepository->getByOrder($orderId);
        
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item->totalPrice;
        }

        // Calculate tax (this would come from location/tax service in real implementation)
        $taxAmount = $subtotal * self::DEFAULT_TAX_RATE;

        // Get discount amount (would come from applied offers)
        $discountAmount = 0;

        // Calculate total
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Calculate item price with modifiers
     */
    public function calculateItemPrice(float $basePrice, array $modifiers = []): float
    {
        $modifiersTotal = 0;
        
        foreach ($modifiers as $modifier) {
            $modifiersTotal += (float)($modifier['price'] ?? 0);
        }

        return $basePrice + $modifiersTotal;
    }

    /**
     * Apply discount to order
     */
    public function applyDiscount(float $totalAmount, float $discountPercentage): float
    {
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and 100');
        }

        return $totalAmount * (1 - $discountPercentage / 100);
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
            'tax' => $order->taxAmount,
            'discount' => $order->discountAmount,
            'total' => $order->totalAmount,
            'estimatedPrepTime' => $this->calculateEstimatedPrepTime($items),
        ];
    }
}