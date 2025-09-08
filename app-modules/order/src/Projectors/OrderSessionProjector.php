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
        // Record search in session events
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'search',
            'event_data' => json_encode([
                'query' => $event->query,
                'filters' => $event->filters,
                'results_count' => $event->resultsCount,
                'search_id' => $event->searchId,
            ]),
            'created_at' => now(),
        ]);

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
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'category_browse',
            'event_data' => json_encode([
                'category_id' => $event->categoryId,
                'category_name' => $event->categoryName,
                'items_viewed' => $event->itemsViewed,
                'time_spent' => $event->timeSpentSeconds,
            ]),
            'created_at' => now(),
        ]);

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
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'item_view',
            'event_data' => json_encode([
                'item_id' => $event->itemId,
                'item_name' => $event->itemName,
                // NO PRICE - not needed for session tracking
                'category' => $event->category,
                'source' => $event->viewSource,
                'duration' => $event->viewDurationSeconds,
            ]),
            'created_at' => now(),
        ]);

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
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'cart_add',
            'event_data' => json_encode([
                'item_id' => $event->itemId,
                'item_name' => $event->itemName,
                'quantity' => $event->quantity,
                // NO UNIT_PRICE - pricing calculated at checkout
                'category' => $event->category,
                'source' => $event->addedFrom,
            ]),
            'created_at' => now(),
        ]);

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
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'cart_remove',
            'event_data' => json_encode([
                'item_id' => $event->itemId,
                'item_name' => $event->itemName,
                'quantity' => $event->removedQuantity,
                'reason' => $event->removalReason,
            ]),
            'created_at' => now(),
        ]);

        $this->updateSessionActivity($event->aggregateRootUuid);
        $this->updateCartItemsCount($event->aggregateRootUuid);
    }

    /**
     * Handle cart modified event
     */
    public function onCartModified(CartModified $event): void
    {
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'cart_modify',
            'event_data' => json_encode([
                'item_id' => $event->itemId,
                'item_name' => $event->itemName,
                'modification' => $event->modificationType,
                'changes' => $event->changes,
            ]),
            'created_at' => now(),
        ]);

        $this->updateSessionActivity($event->aggregateRootUuid);
        $this->updateCartItemsCount($event->aggregateRootUuid);
    }

    /**
     * Handle serving type selected event
     */
    public function onServingTypeSelected(ServingTypeSelected $event): void
    {
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'serving_type',
            'event_data' => json_encode([
                'type' => $event->servingType,
                'previous' => $event->previousType,
                'table_number' => $event->tableNumber,
                'delivery_address' => $event->deliveryAddress,
            ]),
            'created_at' => now(),
        ]);

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
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'customer_info',
            'event_data' => json_encode([
                'fields' => array_keys($event->fields),
                'is_complete' => $event->isComplete,
                'has_errors' => !empty($event->validationErrors),
            ]),
            'created_at' => now(),
        ]);

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
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'payment_method',
            'event_data' => json_encode([
                'method' => $event->paymentMethod,
                'previous' => $event->previousMethod,
            ]),
            'created_at' => now(),
        ]);

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
        DB::table('order_session_events')->insert([
            'session_id' => $event->aggregateRootUuid,
            'event_type' => 'draft_saved',
            'event_data' => json_encode([
                'items_count' => count($event->cartItems),
                // NO SUBTOTAL - pricing calculated at checkout
                'auto_saved' => $event->autoSaved,
            ]),
            'created_at' => now(),
        ]);

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
        // Calculate cart items count from events - NO PRICING
        $cartEvents = DB::table('order_session_events')
            ->where('session_id', $sessionId)
            ->whereIn('event_type', ['cart_add', 'cart_remove', 'cart_modify'])
            ->get();

        $itemsCount = 0;
        $cart = [];

        foreach ($cartEvents as $event) {
            $data = json_decode($event->event_data, true);
            
            switch ($event->event_type) {
                case 'cart_add':
                    $cart[$data['item_id']] = ($cart[$data['item_id']] ?? 0) + $data['quantity'];
                    break;
                case 'cart_remove':
                    $cart[$data['item_id']] = max(0, ($cart[$data['item_id']] ?? 0) - $data['quantity']);
                    if ($cart[$data['item_id']] === 0) {
                        unset($cart[$data['item_id']]);
                    }
                    break;
                case 'cart_modify':
                    if (isset($data['changes'])) {
                        $cart[$data['item_id']] = max(0, $data['changes']['to'] ?? 0);
                        if ($cart[$data['item_id']] === 0) {
                            unset($cart[$data['item_id']]);
                        }
                    }
                    break;
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