<?php

namespace Colame\Order\Services;

use Colame\Order\Aggregates\OrderAggregate;
use Colame\Order\Data\OrderData;
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderSession;
use Colame\Location\Contracts\LocationServiceInterface;
use Colame\Business\Contracts\BusinessContextInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderSessionService
{
    public function __construct(
        private ?LocationServiceInterface $locationService = null,
        private ?BusinessContextInterface $businessContext = null
    ) {}
    /**
     * Start a new order session
     */
    public function startSession(array $data): array
    {
        $orderUuid = (string) Str::uuid();
        
        // Get business context first
        $businessId = null;
        $businessData = null;
        if ($this->businessContext) {
            $currentBusiness = $this->businessContext->getCurrentBusiness();
            if ($currentBusiness) {
                $businessId = $currentBusiness->id;
                $businessData = [
                    'id' => $currentBusiness->id,
                    'name' => $currentBusiness->name,
                    'currency' => $currentBusiness->currency ?? config('money.defaults.currency', 'CLP'),
                ];
            }
        }
        
        // Validate we have a business context
        if (!$businessId) {
            throw new \InvalidArgumentException('No business context available. Please select a business before starting an order session.');
        }
        
        // Get location from the user's current location (server-side)
        $locationId = null;
        $locationData = null;
        $currency = null;
        
        // Get the current user's location from database (set by location switcher)
        if ($this->locationService && auth()->id()) {
            $currentLocation = $this->locationService->getUserCurrentLocation(auth()->id());
            if ($currentLocation) {
                // Validate that location belongs to the current business
                if ($currentLocation->businessId !== $businessId) {
                    throw new \InvalidArgumentException('Selected location does not belong to the current business context.');
                }
                
                $locationId = $currentLocation->id;
                $locationData = [
                    'id' => $currentLocation->id,
                    'name' => $currentLocation->name,
                    'currency' => $currentLocation->currency ?? $businessData['currency'] ?? config('money.defaults.currency', 'CLP'),
                    'timezone' => $currentLocation->timezone ?? config('app.timezone'),
                ];
                $currency = $locationData['currency'];
            }
        }
        
        // Validate we have a location
        if (!$locationId) {
            throw new \InvalidArgumentException('No location available. Please set your current location before starting an order session.');
        }
        
        // Get device info from request
        $deviceInfo = [
            'platform' => $data['platform'] ?? 'web',
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip(),
        ];
        
        // Start the event-sourced session with complete business and location context
        OrderAggregate::retrieve($orderUuid)
            ->initiateSession(
                userId: auth()->id(),
                locationId: $locationId,
                deviceInfo: $deviceInfo,
                referrer: $data['referrer'] ?? request()->headers->get('referer'),
                metadata: [
                    'source' => $data['source'] ?? 'web',
                    'order_type' => $data['order_type'] ?? null,
                    'location_locked' => true,
                    'location' => $locationData, // Store complete location context
                    'business' => $businessData, // Store business context
                    'business_id' => $businessId, // Store business ID for queries
                    'currency' => $currency,
                ]
            )
            ->persist();
        
        return [
            'uuid' => $orderUuid,
            'status' => 'initiated',
            'started_at' => now()->toIso8601String(),
            'location' => $locationData, // Include location in response
            'business' => $businessData, // Include business in response
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
                    $data['validation_errors'] ?? [],
                    $data['is_complete'] ?? false
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
                unitPrice: 0, // Placeholder - real price calculated at checkout
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
            ->modifyCartItem(
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
        // Get session from the event-sourced table
        $session = OrderSession::find($orderUuid);
        
        if ($session && $session->isActive()) {
                // Get cart items from stored_events table
                $cartEvents = DB::table('stored_events')
                    ->where('aggregate_uuid', $orderUuid)
                    ->whereIn('event_class', [
                        'Colame\\Order\\Events\\Session\\ItemAddedToCart',
                        'Colame\\Order\\Events\\Session\\ItemRemovedFromCart',
                        'Colame\\Order\\Events\\Session\\CartModified'
                    ])
                    ->orderBy('created_at')
                    ->get();
                
                $cart = [];
                foreach ($cartEvents as $event) {
                    $eventData = json_decode($event->event_properties, true);
                    $eventClass = $event->event_class;
                    
                    if (str_ends_with($eventClass, 'ItemAddedToCart')) {
                        if (!isset($cart[$eventData['itemId']])) {
                            $cart[$eventData['itemId']] = [
                                'id' => $eventData['itemId'],
                                'name' => $eventData['itemName'],
                                'quantity' => 0,
                                // NO PRICE STORED - will be fetched fresh from items table
                                'category' => $eventData['category'] ?? null,
                            ];
                        }
                        $cart[$eventData['itemId']]['quantity'] += $eventData['quantity'];
                    } elseif (str_ends_with($eventClass, 'ItemRemovedFromCart')) {
                        if (isset($cart[$eventData['itemId']])) {
                            $cart[$eventData['itemId']]['quantity'] -= $eventData['removedQuantity'];
                            if ($cart[$eventData['itemId']]['quantity'] <= 0) {
                                unset($cart[$eventData['itemId']]);
                            }
                        }
                    } elseif (str_ends_with($eventClass, 'CartModified')) {
                        if (isset($cart[$eventData['itemId']]) && isset($eventData['changes'])) {
                            // Apply quantity change from modification
                            $from = $eventData['changes']['from'] ?? 0;
                            $to = $eventData['changes']['to'] ?? 0;
                            $cart[$eventData['itemId']]['quantity'] = $to;
                            
                            // Remove item if quantity becomes 0 or negative
                            if ($cart[$eventData['itemId']]['quantity'] <= 0) {
                                unset($cart[$eventData['itemId']]);
                            }
                        }
                    }
                }
                
                return [
                    'uuid' => $session->uuid,
                    'status' => $session->status,
                    'serving_type' => $session->serving_type,
                    'cart_items' => array_values($cart),
                    'cart_value' => null, // No pricing in sessions
                    'customer_info_complete' => $session->customer_info_complete,
                    'payment_method' => $session->payment_method,
                    'started_at' => $session->started_at,
                    'last_activity_at' => $session->last_activity_at,
                    'location' => $session->getLocationData(), // Include location data
                ];
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
        $session = OrderSession::find($orderUuid);
        
        if ($session && $session->isActive()) {
            // Update last activity
            $session->update([
                'last_activity_at' => now(),
            ]);
            
            return $this->getSessionState($orderUuid);
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
    public function convertToOrder(string $sessionUuid, array $data): array
    {
        $state = $this->getSessionState($sessionUuid);
        
        if ($state['status'] === 'not_found') {
            return ['error' => 'Session not found'];
        }
        
        // Get the session
        $session = OrderSession::find($sessionUuid);
        if (!$session) {
            return ['error' => 'Session not found'];
        }
        
        // Generate a NEW UUID for the order (different from session UUID)
        $orderUuid = (string) Str::uuid();
        
        // Create a new order aggregate with the new UUID
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Start the order (this creates the Order record in the database)
        // Note: staffId should be null if no staff member exists for this user
        $aggregate->startOrder(
            staffId: null, // We don't have staff members yet, so set to null
            locationId: (string) $session->location_id,
            tableNumber: null, // Can be added from customer info if needed
            sessionId: $sessionUuid, // Pass the session UUID as a reference
            metadata: [
                'source' => 'session_conversion',
                'session_uuid' => $sessionUuid,
                'user_id' => $session->user_id ?? auth()->id(), // Store user_id in metadata instead
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );
        
        // Add all cart items as order items - fetch fresh prices from database
        $itemIds = array_column($state['cart_items'], 'id');
        $items = \Colame\Item\Models\Item::whereIn('id', $itemIds)->get()->keyBy('id');
        
        $orderTotal = 0;
        $orderItems = [];
        foreach ($state['cart_items'] as $cartItem) {
            $item = $items[$cartItem['id']] ?? null;
            if (!$item) {
                continue; // Skip if item no longer exists
            }
            
            // Get fresh price from database - NEVER from session
            $currentPrice = $item->sale_price ?? $item->base_price;
            $orderTotal += $currentPrice * $cartItem['quantity'];
            
            $orderItems[] = [
                'item_id' => $cartItem['id'],
                'quantity' => $cartItem['quantity'],
                'unit_price' => $currentPrice, // Fresh price from database
                'notes' => null,
            ];
        }
        
        // Add items to the order
        $aggregate->addItems($orderItems);
        
        // Validate the items with subtotal
        $subtotal = \Akaunting\Money\Money::CLP($orderTotal);
        $aggregate->markItemsAsValidated($orderItems, $subtotal);
        
        // Set payment method (default to cash for now - should come from session or request)
        $paymentMethod = $state['payment_method'] ?? 'cash';
        $aggregate->setPaymentMethod($paymentMethod);
        
        // Now confirm the order
        $aggregate->confirmOrder();
        
        // Debug: Log before persist
        \Illuminate\Support\Facades\Log::info("About to persist order aggregate", ['orderUuid' => $orderUuid]);
        
        $aggregate->persist();
        
        // Debug: Log after persist
        \Illuminate\Support\Facades\Log::info("Order aggregate persisted successfully", ['orderUuid' => $orderUuid]);
        
        // Update session status
        $session = OrderSession::find($sessionUuid);
        if ($session) {
            $session->update([
                'status' => 'converted',
                'order_id' => $orderUuid, // Store reference to the created order
            ]);
        }
        
        // Return the new order UUID (different from session UUID)
        return [
            'order_uuid' => $orderUuid,
            'order_id' => $orderUuid, // UUID is the primary key
            'session_id' => $sessionUuid, // Include session reference
            'status' => 'confirmed',
            'total' => $orderTotal, // Fresh calculated total from database prices
        ];
    }
}