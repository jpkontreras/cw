<?php

namespace Colame\Order\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Events\Session\OrderSessionInitiated;
use Colame\Order\Events\Session\ItemSearched;
use Colame\Order\Events\Session\CategoryBrowsed;
use Colame\Order\Events\Session\ItemViewed;
use Colame\Order\Events\Session\ItemAddedToCart;
use Colame\Order\Events\Session\ItemRemovedFromCart;
use Colame\Order\Events\Session\CartModified;
use Colame\Order\Events\Session\ServingTypeSelected;
use Colame\Order\Events\Session\CustomerInfoEntered;
use Colame\Order\Events\Session\PaymentMethodSelected;
use Colame\Order\Events\Session\OrderDraftSaved;
use Colame\Order\Events\Session\SessionAbandoned;
use Illuminate\Support\Facades\DB;

class OrderSessionProjector extends Projector
{
    /**
     * Handle session initiated event
     */
    public function onOrderSessionInitiated(OrderSessionInitiated $event): void
    {
        // Extract business_id from metadata if available
        $businessId = isset($event->metadata['business_id']) ? $event->metadata['business_id'] : null;
        
        // Create session record in database
        DB::table('order_sessions')->insert([
            'uuid' => $event->aggregateRootUuid,
            'user_id' => $event->userId,
            'location_id' => $event->locationId,
            'business_id' => $businessId,
            'status' => 'initiated',
            'device_info' => json_encode($event->deviceInfo),
            'referrer' => $event->referrer,
            'metadata' => json_encode($event->metadata),
            'started_at' => now(),
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Track in analytics
        $this->trackAnalytics($event->aggregateRootUuid, 'session_started', [
            'user_id' => $event->userId,
            'location_id' => $event->locationId,
            'platform' => $event->deviceInfo['platform'] ?? 'unknown',
        ]);
    }

    /**
     * Handle item searched event
     */
    public function onItemSearched(ItemSearched $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        // Update session activity
        $this->updateSessionActivity($event->aggregateRootUuid);

        // Track search terms for analytics
        $this->trackSearchTerm($event->query, $event->resultsCount);
    }

    /**
     * Handle category browsed event
     */
    public function onCategoryBrowsed(CategoryBrowsed $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        $this->updateSessionActivity($event->aggregateRootUuid);
        
        // Track category popularity
        DB::table('category_analytics')
            ->where('category_id', $event->categoryId)
            ->increment('view_count');
    }

    /**
     * Handle item viewed event
     */
    public function onItemViewed(ItemViewed $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        $this->updateSessionActivity($event->aggregateRootUuid);
        
        // Track item popularity
        DB::table('item_analytics')
            ->where('item_id', $event->itemId)
            ->increment('view_count');
    }

    /**
     * Handle item added to cart event
     */
    public function onItemAddedToCart(ItemAddedToCart $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        // Update session to 'cart_building' status
        DB::table('order_sessions')
            ->where('uuid', $event->aggregateRootUuid)
            ->where('status', 'initiated')
            ->update([
                'status' => 'cart_building',
                'updated_at' => now(),
            ]);

        $this->updateSessionActivity($event->aggregateRootUuid);
        $this->updateCartItemsCount($event->aggregateRootUuid);
        
        // Track conversion funnel
        $this->trackAnalytics($event->aggregateRootUuid, 'cart_started', [
            'first_item' => $event->itemName,
            'source' => $event->addedFrom,
        ]);
    }

    /**
     * Handle item removed from cart event
     */
    public function onItemRemovedFromCart(ItemRemovedFromCart $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        $this->updateSessionActivity($event->aggregateRootUuid);
        $this->updateCartItemsCount($event->aggregateRootUuid);
    }

    /**
     * Handle cart modified event
     */
    public function onCartModified(CartModified $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        $this->updateSessionActivity($event->aggregateRootUuid);
        $this->updateCartItemsCount($event->aggregateRootUuid);
    }

    /**
     * Handle serving type selected event
     */
    public function onServingTypeSelected(ServingTypeSelected $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        // Update session status if progressing
        DB::table('order_sessions')
            ->where('uuid', $event->aggregateRootUuid)
            ->where('status', 'cart_building')
            ->update([
                'status' => 'details_collecting',
                'serving_type' => $event->servingType,
                'updated_at' => now(),
            ]);

        $this->updateSessionActivity($event->aggregateRootUuid);
    }

    /**
     * Handle customer info entered event
     */
    public function onCustomerInfoEntered(CustomerInfoEntered $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        // Update session with customer info completion status
        if ($event->isComplete) {
            DB::table('order_sessions')
                ->where('uuid', $event->aggregateRootUuid)
                ->update([
                    'customer_info_complete' => true,
                    'updated_at' => now(),
                ]);
        }

        $this->updateSessionActivity($event->aggregateRootUuid);
    }

    /**
     * Handle payment method selected event
     */
    public function onPaymentMethodSelected(PaymentMethodSelected $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        DB::table('order_sessions')
            ->where('uuid', $event->aggregateRootUuid)
            ->update([
                'payment_method' => $event->paymentMethod,
                'updated_at' => now(),
            ]);

        $this->updateSessionActivity($event->aggregateRootUuid);
    }

    /**
     * Handle order draft saved event
     */
    public function onOrderDraftSaved(OrderDraftSaved $event): void
    {
        // Events are already stored in stored_events table via event sourcing
        // No need to duplicate in order_session_events

        // Store draft data
        DB::table('order_drafts')->updateOrInsert(
            ['session_id' => $event->aggregateRootUuid],
            [
                'cart_items' => json_encode($event->cartItems),
                'customer_info' => json_encode($event->customerInfo),
                'serving_type' => $event->servingType,
                'payment_method' => $event->paymentMethod,
                // NO SUBTOTAL - pricing calculated at checkout
                'auto_saved' => $event->autoSaved,
                'saved_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->updateSessionActivity($event->aggregateRootUuid);
    }

    /**
     * Handle session abandoned event
     */
    public function onSessionAbandoned(SessionAbandoned $event): void
    {
        DB::table('order_sessions')
            ->where('uuid', $event->aggregateRootUuid)
            ->update([
                'status' => 'abandoned',
                'abandonment_reason' => $event->reason,
                'session_duration' => $event->sessionDurationSeconds,
                'cart_items_count' => $event->itemsInCart,
                // NO cart_value - pricing not tracked in sessions
                'abandoned_at' => now(),
                'updated_at' => now(),
            ]);

        // Track abandonment analytics
        $this->trackAnalytics($event->aggregateRootUuid, 'session_abandoned', [
            'reason' => $event->reason,
            'duration_seconds' => $event->sessionDurationSeconds,
            // NO cart_value - pricing not tracked in sessions
            'items_count' => $event->itemsInCart,
            'last_activity' => $event->lastActivity,
        ]);
    }

    /**
     * Update session last activity timestamp
     */
    private function updateSessionActivity(string $sessionId): void
    {
        DB::table('order_sessions')
            ->where('uuid', $sessionId)
            ->update([
                'last_activity_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Update cart items count in session (NO PRICING)
     */
    private function updateCartItemsCount(string $sessionId): void
    {
        // Calculate cart items count from stored_events table - NO PRICING
        $cartEvents = DB::table('stored_events')
            ->where('aggregate_uuid', $sessionId)
            ->whereIn('event_class', [
                ItemAddedToCart::class,
                ItemRemovedFromCart::class,
                CartModified::class
            ])
            ->orderBy('created_at')
            ->get();

        $itemsCount = 0;
        $cart = [];

        foreach ($cartEvents as $event) {
            $eventData = json_decode($event->event_properties, true);
            $eventClass = $event->event_class;
            
            if ($eventClass === ItemAddedToCart::class) {
                $cart[$eventData['itemId']] = ($cart[$eventData['itemId']] ?? 0) + $eventData['quantity'];
            } elseif ($eventClass === ItemRemovedFromCart::class) {
                $cart[$eventData['itemId']] = max(0, ($cart[$eventData['itemId']] ?? 0) - $eventData['removedQuantity']);
                if ($cart[$eventData['itemId']] === 0) {
                    unset($cart[$eventData['itemId']]);
                }
            } elseif ($eventClass === CartModified::class) {
                if (isset($eventData['changes'])) {
                    $cart[$eventData['itemId']] = max(0, $eventData['changes']['to'] ?? 0);
                    if ($cart[$eventData['itemId']] === 0) {
                        unset($cart[$eventData['itemId']]);
                    }
                }
            }
        }

        // Only count items - NO PRICING
        foreach ($cart as $quantity) {
            $itemsCount += $quantity;
        }

        DB::table('order_sessions')
            ->where('uuid', $sessionId)
            ->update([
                'cart_items_count' => $itemsCount,
                // NO cart_value - removed completely
                'updated_at' => now(),
            ]);
    }

    /**
     * Track analytics event
     */
    private function trackAnalytics(string $sessionId, string $event, array $data = []): void
    {
        DB::table('order_analytics')->insert([
            'session_id' => $sessionId,
            'event_name' => $event,
            'event_data' => json_encode($data),
            'created_at' => now(),
        ]);
    }

    /**
     * Track search terms for analytics
     */
    private function trackSearchTerm(string $query, int $resultsCount): void
    {
        DB::table('search_analytics')->insert([
            'search_term' => $query,
            'results_count' => $resultsCount,
            'searched_at' => now(),
            'created_at' => now(),
        ]);
    }
}