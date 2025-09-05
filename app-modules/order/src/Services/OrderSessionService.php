<?php

namespace Colame\Order\Services;

use Colame\Order\Aggregates\OrderAggregate;
use Colame\Order\Data\OrderData;
use Colame\Order\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderSessionService
{
    /**
     * Start a new order session
     */
    public function startSession(array $data): array
    {
        $orderUuid = (string) Str::uuid();
        
        // Get device info from request
        $deviceInfo = [
            'platform' => $data['platform'] ?? 'web',
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip(),
        ];
        
        // Start the event-sourced session
        OrderAggregate::retrieve($orderUuid)
            ->initiateSession(
                userId: auth()->id(),
                locationId: $data['location_id'] ?? 1,
                deviceInfo: $deviceInfo,
                referrer: $data['referrer'] ?? request()->headers->get('referer'),
                metadata: [
                    'source' => $data['source'] ?? 'web',
                    'order_type' => $data['order_type'] ?? null,
                ]
            )
            ->persist();
        
        // Cache session for quick access
        Cache::put("order_session:{$orderUuid}", [
            'uuid' => $orderUuid,
            'user_id' => auth()->id(),
            'location_id' => $data['location_id'] ?? 1,
            'status' => 'initiated',
            'started_at' => now(),
        ], now()->addHours(24));
        
        return [
            'uuid' => $orderUuid,
            'status' => 'initiated',
            'started_at' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Track an event in the session
     */
    public function trackEvent(string $orderUuid, array $data): void
    {
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        switch ($data['event']) {
            case 'search':
                $aggregate->recordSearch(
                    $data['query'],
                    $data['filters'] ?? [],
                    $data['results_count'] ?? 0,
                    $data['search_id'] ?? Str::uuid()
                );
                break;
                
            case 'category_browse':
                $aggregate->browseCategory(
                    $data['category_id'],
                    $data['category_name'],
                    $data['items_viewed'] ?? 0,
                    $data['time_spent'] ?? 0
                );
                break;
                
            case 'item_view':
                $aggregate->viewItem(
                    $data['item_id'],
                    $data['item_name'],
                    $data['price'],
                    $data['category'] ?? null,
                    $data['source'] ?? 'unknown',
                    $data['duration'] ?? 0
                );
                break;
                
            case 'serving_type':
                $aggregate->selectServingType(
                    $data['type'],
                    $data['previous'] ?? null,
                    $data['table_number'] ?? null,
                    $data['delivery_address'] ?? null
                );
                break;
                
            case 'customer_info':
                $aggregate->enterCustomerInfo(
                    $data['fields'],
                    $data['is_complete'] ?? false,
                    $data['validation_errors'] ?? []
                );
                break;
                
            case 'payment_method':
                $aggregate->selectPaymentMethod(
                    $data['method'],
                    $data['previous'] ?? null
                );
                break;
        }
        
        $aggregate->persist();
    }
    
    /**
     * Add item to cart
     */
    public function addToCart(string $orderUuid, array $data): void
    {
        OrderAggregate::retrieve($orderUuid)
            ->addToCart(
                itemId: $data['item_id'],
                itemName: $data['item_name'],
                quantity: $data['quantity'],
                unitPrice: $data['unit_price'],
                category: $data['category'] ?? null,
                modifiers: $data['modifiers'] ?? [],
                notes: $data['notes'] ?? null,
                addedFrom: $data['added_from'] ?? 'unknown'
            )
            ->persist();
    }
    
    /**
     * Remove item from cart
     */
    public function removeFromCart(string $orderUuid, array $data): void
    {
        OrderAggregate::retrieve($orderUuid)
            ->removeFromCart(
                $data['item_id'],
                $data['item_name'],
                $data['quantity'],
                $data['reason'] ?? 'user_removed'
            )
            ->persist();
    }
    
    /**
     * Update cart item
     */
    public function updateCartItem(string $orderUuid, array $data): void
    {
        OrderAggregate::retrieve($orderUuid)
            ->modifyCart(
                $data['item_id'],
                $data['item_name'],
                $data['modification_type'],
                $data['changes']
            )
            ->persist();
    }
    
    /**
     * Get current session state
     */
    public function getSessionState(string $orderUuid): array
    {
        // Try cache first
        $cached = Cache::get("order_session:{$orderUuid}");
        if ($cached) {
            // Get full state from database
            $session = DB::table('order_sessions')
                ->where('uuid', $orderUuid)
                ->first();
                
            if ($session) {
                // Get cart items from events
                $cartEvents = DB::table('order_session_events')
                    ->where('session_id', $orderUuid)
                    ->whereIn('event_type', ['cart_add', 'cart_remove', 'cart_modify'])
                    ->orderBy('created_at')
                    ->get();
                
                $cart = [];
                foreach ($cartEvents as $event) {
                    $data = json_decode($event->event_data, true);
                    
                    switch ($event->event_type) {
                        case 'cart_add':
                            if (!isset($cart[$data['item_id']])) {
                                $cart[$data['item_id']] = [
                                    'id' => $data['item_id'],
                                    'name' => $data['item_name'],
                                    'quantity' => 0,
                                    'unit_price' => $data['unit_price'],
                                    'category' => $data['category'] ?? null,
                                ];
                            }
                            $cart[$data['item_id']]['quantity'] += $data['quantity'];
                            break;
                            
                        case 'cart_remove':
                            if (isset($cart[$data['item_id']])) {
                                $cart[$data['item_id']]['quantity'] -= $data['quantity'];
                                if ($cart[$data['item_id']]['quantity'] <= 0) {
                                    unset($cart[$data['item_id']]);
                                }
                            }
                            break;
                    }
                }
                
                return [
                    'uuid' => $session->uuid,
                    'status' => $session->status,
                    'serving_type' => $session->serving_type,
                    'cart_items' => array_values($cart),
                    'cart_value' => $session->cart_value,
                    'customer_info_complete' => $session->customer_info_complete,
                    'payment_method' => $session->payment_method,
                    'started_at' => $session->started_at,
                    'last_activity_at' => $session->last_activity_at,
                ];
            }
        }
        
        return [
            'uuid' => $orderUuid,
            'status' => 'not_found',
            'cart_items' => [],
        ];
    }
    
    /**
     * Recover a session
     */
    public function recoverSession(string $orderUuid): array
    {
        $state = $this->getSessionState($orderUuid);
        
        if ($state['status'] !== 'not_found') {
            // Update last activity
            DB::table('order_sessions')
                ->where('uuid', $orderUuid)
                ->update([
                    'last_activity_at' => now(),
                    'updated_at' => now(),
                ]);
            
            // Refresh cache
            Cache::put("order_session:{$orderUuid}", [
                'uuid' => $orderUuid,
                'status' => $state['status'],
                'recovered_at' => now(),
            ], now()->addHours(24));
            
            return $state;
        }
        
        return ['error' => 'Session not found'];
    }
    
    /**
     * Save draft
     */
    public function saveDraft(string $orderUuid, bool $autoSaved = false): void
    {
        $state = $this->getSessionState($orderUuid);
        
        if ($state['status'] !== 'not_found') {
            OrderAggregate::retrieve($orderUuid)
                ->saveDraft(
                    $state['cart_items'],
                    [],  // customer info from state
                    $state['serving_type'],
                    $state['payment_method'],
                    $state['cart_value'],
                    $autoSaved
                )
                ->persist();
        }
    }
    
    /**
     * Convert session to order
     */
    public function convertToOrder(string $orderUuid, array $data): array
    {
        $state = $this->getSessionState($orderUuid);
        
        if ($state['status'] === 'not_found') {
            return ['error' => 'Session not found'];
        }
        
        // Convert the session to an actual order
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Add all cart items as order items
        foreach ($state['cart_items'] as $item) {
            $aggregate->addItems([[
                'item_id' => $item['id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'notes' => null,
            ]]);
        }
        
        // Confirm the order
        $aggregate->confirmOrder();
        $aggregate->persist();
        
        // Update session status
        DB::table('order_sessions')
            ->where('uuid', $orderUuid)
            ->update([
                'status' => 'converted',
                'converted_at' => now(),
                'updated_at' => now(),
            ]);
        
        // Clear cache
        Cache::forget("order_session:{$orderUuid}");
        
        return [
            'order_uuid' => $orderUuid,
            'status' => 'confirmed',
            'total' => $state['cart_value'],
        ];
    }
}