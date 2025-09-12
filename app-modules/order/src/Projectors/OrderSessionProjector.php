<?php

declare(strict_types=1);

namespace Colame\Order\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Events\Session\OrderSessionInitiated;
use Colame\Order\Events\Session\ItemAddedToCart;
use Colame\Order\Events\Session\ItemRemovedFromCart;
use Colame\Order\Events\Session\CartModified;
use Colame\Order\Events\Session\ServingTypeSelected;
use Colame\Order\Events\Session\CustomerInfoEntered;
use Colame\Order\Events\Session\PaymentMethodSelected;
use Colame\Order\Events\Session\OrderDraftSaved;
use Colame\Order\Events\Session\SessionAbandoned;
use Colame\Order\Events\Session\SessionConverted;
use Colame\Order\Models\OrderSession;

class OrderSessionProjector extends Projector
{
    /**
     * Handle session initiated event
     */
    public function onOrderSessionInitiated(OrderSessionInitiated $event): void
    {
        OrderSession::create([
            'uuid' => $event->aggregateRootUuid(),
            'user_id' => $event->userId,
            'location_id' => $event->locationId,
            'status' => 'initiated',
            'device_info' => $event->deviceInfo,
            'referrer' => $event->referrer,
            'metadata' => $event->metadata,
            'cart_items' => [],
            'started_at' => $event->createdAt(),
            'last_activity_at' => $event->createdAt(),
        ]);
    }

    /**
     * Handle item added to cart event
     */
    public function onItemAddedToCart(ItemAddedToCart $event): void
    {
        $session = OrderSession::find($event->aggregateRootUuid());
        if (!$session) {
            return;
        }

        $cartItems = $session->cart_items ?? [];
        $itemId = $event->itemId;

        if (!isset($cartItems[$itemId])) {
            $cartItems[$itemId] = [
                'id' => $itemId,
                'name' => $event->itemName,
                'quantity' => 0,
                'category' => $event->category,
                'modifiers' => $event->modifiers,
                'notes' => $event->notes,
            ];
        }
        $cartItems[$itemId]['quantity'] += $event->quantity;

        $session->update([
            'cart_items' => $cartItems,
            'last_activity_at' => $event->createdAt(),
        ]);
    }

    /**
     * Handle item removed from cart event
     */
    public function onItemRemovedFromCart(ItemRemovedFromCart $event): void
    {
        $session = OrderSession::find($event->aggregateRootUuid());
        if (!$session) {
            return;
        }

        $cartItems = $session->cart_items ?? [];
        if (isset($cartItems[$event->itemId])) {
            $cartItems[$event->itemId]['quantity'] -= $event->removedQuantity;
            if ($cartItems[$event->itemId]['quantity'] <= 0) {
                unset($cartItems[$event->itemId]);
            }
        }

        $session->update([
            'cart_items' => $cartItems,
            'last_activity_at' => $event->createdAt(),
        ]);
    }

    /**
     * Handle cart modified event
     */
    public function onCartModified(CartModified $event): void
    {
        $session = OrderSession::find($event->aggregateRootUuid());
        if (!$session) {
            return;
        }

        $cartItems = $session->cart_items ?? [];
        if (isset($cartItems[$event->itemId]) && isset($event->changes['to'])) {
            $cartItems[$event->itemId]['quantity'] = $event->changes['to'];
            if ($cartItems[$event->itemId]['quantity'] <= 0) {
                unset($cartItems[$event->itemId]);
            }
        }

        $session->update([
            'cart_items' => $cartItems,
            'last_activity_at' => $event->createdAt(),
        ]);
    }

    /**
     * Handle serving type selected event
     */
    public function onServingTypeSelected(ServingTypeSelected $event): void
    {
        OrderSession::where('uuid', $event->aggregateRootUuid())->update([
            'serving_type' => $event->servingType,
            'table_number' => $event->tableNumber ?? null,
            'delivery_address' => $event->deliveryAddress ?? null,
            'last_activity_at' => $event->createdAt(),
        ]);
    }

    /**
     * Handle customer info entered event
     */
    public function onCustomerInfoEntered(CustomerInfoEntered $event): void
    {
        OrderSession::where('uuid', $event->aggregateRootUuid())->update([
            'customer_info_complete' => $event->isComplete,
            'last_activity_at' => $event->createdAt(),
        ]);
    }

    /**
     * Handle payment method selected event
     */
    public function onPaymentMethodSelected(PaymentMethodSelected $event): void
    {
        OrderSession::where('uuid', $event->aggregateRootUuid())->update([
            'payment_method' => $event->paymentMethod,
            'last_activity_at' => $event->createdAt(),
        ]);
    }

    /**
     * Handle draft saved event
     */
    public function onOrderDraftSaved(OrderDraftSaved $event): void
    {
        OrderSession::where('uuid', $event->aggregateRootUuid())->update([
            'status' => 'draft',
            'draft_saved_at' => $event->createdAt(),
            'last_activity_at' => $event->createdAt(),
        ]);
    }

    /**
     * Handle session abandoned event
     */
    public function onSessionAbandoned(SessionAbandoned $event): void
    {
        OrderSession::where('uuid', $event->aggregateRootUuid())->update([
            'status' => 'abandoned',
            'abandonment_reason' => $event->reason ?? null,
            'abandoned_at' => $event->createdAt(),
        ]);
    }
    
    /**
     * Handle session converted to order event
     */
    public function onSessionConverted(SessionConverted $event): void
    {
        OrderSession::where('uuid', $event->aggregateRootUuid())->update([
            'status' => 'converted',
            'order_id' => $event->orderId,
            'converted_at' => $event->createdAt(),
            'last_activity_at' => $event->createdAt(),
        ]);
    }
}