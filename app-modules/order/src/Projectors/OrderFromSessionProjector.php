<?php

declare(strict_types=1);

namespace Colame\Order\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Events\Session\SessionConverted;
use Colame\Order\Models\Order;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Akaunting\Money\Money;
use Akaunting\Money\Currency;

class OrderFromSessionProjector extends Projector
{
    public function __construct(
        private ?ItemRepositoryInterface $itemRepository = null
    ) {}
    
    /**
     * Create order when session is converted
     */
    public function onSessionConverted(SessionConverted $event): void
    {
        // Extract data from metadata
        $cartItems = $event->metadata['cart_items'] ?? [];
        $currency = $event->metadata['currency'] ?? 'CLP';
        
        // Calculate totals
        $totals = $this->calculateTotals($cartItems, $currency);
        
        // Create or update order record
        Order::updateOrCreate(
            ['id' => $event->orderId],
            [
                'location_id' => $event->metadata['location_id'] ?? 1,
                'user_id' => $event->metadata['user_id'] ?? null,
                'status' => 'confirmed',
                'payment_method' => $event->paymentMethod ?? 'cash',
                'subtotal' => $totals['subtotal'],
                'tax' => 0,
                'discount' => 0,
                'total' => $totals['total'],
                'customer_name' => $event->customerName,
                'customer_phone' => $event->customerPhone,
                'customer_email' => $event->customerEmail,
                'notes' => $event->notes,
                'metadata' => [
                    'source' => 'session_conversion',
                    'converted_at' => $event->createdAt(),
                    'cart_items' => $cartItems,
                ],
                'confirmed_at' => $event->createdAt(),
            ]
        );
    }
    
    /**
     * Calculate order totals from cart items
     */
    private function calculateTotals(array $cartItems, string $currency = 'CLP'): array
    {
        if (empty($cartItems) || !$this->itemRepository) {
            return ['subtotal' => 0, 'total' => 0];
        }
        
        $itemIds = array_column($cartItems, 'id');
        $items = $this->itemRepository->getMultipleItemDetails($itemIds);
        
        $subtotal = new Money(0, new Currency($currency));
        foreach ($cartItems as $cartItem) {
            $item = $items[$cartItem['id']] ?? null;
            if ($item) {
                // Handle both array and object formats
                $price = is_array($item)
                    ? ($item['salePrice'] ?? $item['basePrice'])
                    : ($item->salePrice ?? $item->basePrice);
                $itemTotal = new Money($price * $cartItem['quantity'], new Currency($currency));
                $subtotal = $subtotal->add($itemTotal);
            }
        }
        
        return [
            'subtotal' => $subtotal->getAmount(),
            'total' => $subtotal->getAmount(),
        ];
    }
}