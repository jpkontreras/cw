<?php

namespace Colame\Order\Services;

use Colame\Order\Aggregates\OrderAggregate;
use Colame\Order\Data\Session\OrderContextData;
use Colame\Order\Data\Session\StartOrderFlowData;
use Colame\Order\Exceptions\OrderContextException;
use Colame\Order\Models\OrderSession;
use Colame\Location\Contracts\UserLocationServiceInterface;
use Illuminate\Support\Str;

class OrderSessionService
{
    public function __construct(
        private ?UserLocationServiceInterface $userLocationService = null
    ) {}
    /**
     * Start a new order session with proper business and location context resolution
     * Uses existing location module services for context resolution
     */
    public function startSession(StartOrderFlowData $data): array
    {
        // Get effective location which contains all context needed for the order
        $context = $this->getLocationContext();
        
        // Generate UUID using Str::orderedUuid for better indexing
        $orderUuid = (string) Str::orderedUuid();
        
        // Get device info from data object or fallback to request helpers
        $deviceInfo = [
            'platform' => $data->platform?->value ?? 'web',
            'user_agent' => $data->userAgent ?? request()?->userAgent(),
            'ip' => $data->ipAddress ?? request()?->ip(),
        ];
        
        // Build metadata from context and request data
        $metadata = array_merge(
            $context->toMetadata(),
            [
                'source' => $data->source ?? 'web',
                'order_type' => $data->orderType?->value ?? null,
                'location_locked' => true,
            ]
        );
        
        // Start the event-sourced session with complete context
        OrderAggregate::retrieve($orderUuid)
            ->initiateSession(
                userId: $context->userId,
                locationId: $context->locationId,
                deviceInfo: $deviceInfo,
                referrer: $data->referrer ?? request()?->headers->get('referer'),
                metadata: $metadata
            )
            ->persist();
        
        return [
            'uuid' => $orderUuid,
            'status' => 'initiated',
            'started_at' => now()->toIso8601String(),
            'location' => [
                'id' => $context->locationData->id,
                'name' => $context->locationData->name,
                'currency' => $context->locationData->currency,
                'timezone' => $context->locationData->timezone,
            ],
            'business' => $context->businessData ? [
                'id' => $context->businessData->id,
                'name' => $context->businessData->name,
            ] : null,
        ];
    }

    /**
     * Get location context for the order
     * Uses UserLocationService::getEffectiveLocation which provides complete context
     */
    private function getLocationContext(): OrderContextData
    {
        $user = auth()->user();
        
        if (!$user || !$this->userLocationService) {
            throw OrderContextException::noLocationAvailable();
        }
        
        // Get effective location - it has everything we need:
        // - businessId (locations MUST belong to a business)
        // - currency, timezone, and all other context
        $locationData = $this->userLocationService->getEffectiveLocation($user);
        
        if (!$locationData) {
            throw OrderContextException::noLocationAvailable();
        }
        
        // Since location MUST have a business (domain requirement),
        // we can trust locationData->businessId exists
        return new OrderContextData(
            locationId: $locationData->id,
            locationData: $locationData,
            businessId: $locationData->businessId,
            businessData: null, // Not needed since location has businessId
            currency: $locationData->currency,
            timezone: $locationData->timezone,
            userId: $user->id
        );
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
        // Now OrderSession is the read model maintained by the projector
        $session = OrderSession::find($orderUuid);
        
        if ($session && $session->isActive()) {
            return [
                'uuid' => $session->uuid,
                'status' => $session->status,
                'serving_type' => $session->serving_type,
                'cart_items' => array_values($session->cart_items ?? []),
                'cart_value' => null, // No pricing in sessions
                'customer_info_complete' => $session->customer_info_complete,
                'payment_method' => $session->payment_method,
                'started_at' => $session->started_at,
                'last_activity_at' => $session->last_activity_at,
                'location' => $session->getLocationData(),
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
        
        if (!$session) {
            return [
                'error' => 'Session not found or expired',
                'flash' => [
                    'error' => 'Session not found or expired',
                    'success' => null,
                    'warning' => null,
                    'info' => null,
                ],
            ];
        }
        
        if (!$session->isActive()) {
            return [
                'error' => 'Session is no longer active',
                'flash' => [
                    'error' => 'Session is no longer active',
                    'success' => null,
                    'warning' => null,
                    'info' => null,
                ],
            ];
        }
        
        // Update last activity
        $session->update([
            'last_activity_at' => now(),
        ]);
        
        return $this->getSessionState($orderUuid);
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
                'name' => $item->name, // Add item name from database
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
        
        // Fire SessionConverted event for the session aggregate
        $sessionAggregate = OrderAggregate::retrieve($sessionUuid);
        $sessionAggregate->recordThat(new \Colame\Order\Events\Session\SessionConverted(
            aggregateRootUuid: $sessionUuid,
            orderId: $orderUuid,
            paymentMethod: $data['payment_method'] ?? null,
            customerName: $data['customer_name'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
            customerEmail: $data['customer_email'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: [
                'converted_at' => now()->toIso8601String(),
                'order_total' => $orderTotal,
            ]
        ));
        $sessionAggregate->persist();
        
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