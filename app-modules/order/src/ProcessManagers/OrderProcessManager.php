<?php

declare(strict_types=1);

namespace Colame\Order\ProcessManagers;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Aggregates\OrderSession;
use Colame\Order\Events\SessionEvents\CartItemAdded;
use Colame\Order\Events\OrderEvents\OrderConverted;
use Colame\Order\Events\OrderEvents\OrderPlaced;
use Colame\Order\Events\OrderEvents\PaymentProcessed;
use Colame\Order\Events\OrderEvents\OrderStatusTransitioned;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Location\Contracts\LocationRepositoryInterface;
use Colame\Offer\Contracts\OfferRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Process manager for complex order workflows
 * Coordinates between aggregates and external modules
 */
class OrderProcessManager extends Projector
{
    public function __construct(
        private ?ItemRepositoryInterface $itemRepo = null,
        private ?LocationRepositoryInterface $locationRepo = null,
        private ?OfferRepositoryInterface $offerRepo = null
    ) {}

    /**
     * When item is added to cart, validate and enrich with item details
     */
    public function onCartItemAdded(CartItemAdded $event): void
    {
        if (!$this->itemRepo) {
            return;
        }

        try {
            // Validate item exists and is available
            $item = $this->itemRepo->find($event->itemId);
            
            if (!$item || !$item->isAvailable) {
                // Item not available - trigger removal
                OrderSession::retrieve($event->aggregateRootUuid())
                    ->removeCartItem($event->itemIndex ?? 0, 'Item not available')
                    ->persist();
                
                Log::warning('Item not available for order', [
                    'session_id' => $event->sessionId,
                    'item_id' => $event->itemId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to validate cart item', [
                'session_id' => $event->sessionId,
                'item_id' => $event->itemId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * When order is converted, validate location and apply promotions
     */
    public function onOrderConverted(OrderConverted $event): void
    {
        // Validate location
        if ($this->locationRepo) {
            try {
                $location = $this->locationRepo->find($event->locationId);
                
                if (!$location || !$location->isActive) {
                    Log::error('Invalid location for order', [
                        'order_id' => $event->orderId,
                        'location_id' => $event->locationId,
                    ]);
                    
                    // Could trigger order cancellation here
                    return;
                }
            } catch (\Exception $e) {
                Log::error('Failed to validate location', [
                    'order_id' => $event->orderId,
                    'location_id' => $event->locationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Auto-apply promotions
        if ($this->offerRepo && !empty($event->offerCodes)) {
            try {
                foreach ($event->offerCodes as $code) {
                    $offer = $this->offerRepo->findByCode($code);
                    
                    if ($offer && $offer->isValid()) {
                        // Apply promotion to order
                        OrderSession::retrieve($event->aggregateRootUuid())
                            ->applyPromotion(
                                $offer->id,
                                $code,
                                $offer->type,
                                $offer->value,
                                $offer->calculateDiscount($event->total)
                            )
                            ->persist();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to apply promotions', [
                    'order_id' => $event->orderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * When order is placed, auto-confirm if payment is processed
     */
    public function onOrderPlaced(OrderPlaced $event): void
    {
        // Auto-confirm orders that are pre-paid
        if ($event->paymentStatus === 'paid') {
            OrderSession::retrieve($event->aggregateRootUuid())
                ->confirmOrder()
                ->persist();
        }
    }

    /**
     * When payment is processed, auto-transition order status
     */
    public function onPaymentProcessed(PaymentProcessed $event): void
    {
        if ($event->status === 'completed') {
            // Get current order status
            $order = \Colame\Order\Models\Order::find($event->orderId);
            
            if ($order && $order->status === 'placed') {
                // Auto-confirm paid orders
                OrderSession::retrieve($event->aggregateRootUuid())
                    ->confirmOrder()
                    ->persist();
            }
        }
    }

    /**
     * Handle order status transitions for workflow automation
     */
    public function onOrderStatusTransitioned(OrderStatusTransitioned $event): void
    {
        switch ($event->toStatus) {
            case 'confirmed':
                // Auto-start preparation for confirmed orders
                OrderSession::retrieve($event->aggregateRootUuid())
                    ->startPreparation()
                    ->persist();
                break;
                
            case 'ready':
                // Notify for pickup/delivery
                $this->notifyOrderReady($event->orderId);
                break;
                
            case 'delivered':
                // Auto-complete delivered orders after a delay
                $this->scheduleOrderCompletion($event->orderId);
                break;
        }
    }

    /**
     * Notify that order is ready
     */
    private function notifyOrderReady(string $orderId): void
    {
        // This would integrate with notification service
        Log::info('Order ready for pickup/delivery', ['order_id' => $orderId]);
    }

    /**
     * Schedule order completion
     */
    private function scheduleOrderCompletion(string $orderId): void
    {
        // This would schedule a job to auto-complete the order
        Log::info('Scheduling order completion', ['order_id' => $orderId]);
    }
}