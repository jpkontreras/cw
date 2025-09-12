<?php

namespace Colame\Order\Services;

use Colame\Order\Aggregates\OrderAggregate;
use Colame\Order\Data\Session\OrderContextData;
use Colame\Order\Data\Session\StartOrderFlowData;
use Colame\Order\Events\Session\SessionConverted;
use Colame\Order\Exceptions\OrderContextException;
use Colame\Order\Models\OrderSession;
use Colame\Location\Contracts\UserLocationServiceInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Str;
use Akaunting\Money\Money;
use Akaunting\Money\Currency;

class OrderSessionService
{
    public function __construct(
        private ?UserLocationServiceInterface $userLocationService = null,
        private ?ItemRepositoryInterface $itemRepository = null
    ) {}
    /**
     * Start a new order session with proper business and location context resolution
     * Uses existing location module services for context resolution
     */
    public function startSession(StartOrderFlowData $data): array
    {
        $context = $this->getLocationContext();
        
        $orderUuid = (string) Str::orderedUuid();
        $deviceInfo = [
            'platform' => $data->platform?->value ?? 'web',
            'user_agent' => $data->userAgent ?? request()?->userAgent(),
            'ip' => $data->ipAddress ?? request()?->ip(),
        ];
        
        $metadata = array_merge(
            $context->toMetadata(),
            [
                'source' => $data->source ?? 'web',
                'order_type' => $data->orderType?->value ?? null,
                'location_locked' => true,
            ]
        );
        
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
        $session = OrderSession::find($sessionUuid);
        if (!$session || !$session->isActive()) {
            return ['error' => 'Session not found or not active'];
        }
        
        // Get location data for currency
        $locationData = $session->getLocationData();
        $currency = $locationData['currency'] ?? 'CLP';
        
        // Continue with same aggregate
        $aggregate = OrderAggregate::retrieve($sessionUuid);
        
        // Fire conversion event
        $aggregate->recordThat(new SessionConverted(
            aggregateRootUuid: $sessionUuid,
            orderId: $sessionUuid,
            paymentMethod: $data['payment_method'] ?? $session->payment_method ?? 'cash',
            customerName: $data['customer_name'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
            customerEmail: $data['customer_email'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: [
                'location_id' => $session->location_id,
                'user_id' => $session->user_id,
                'cart_items' => $session->cart_items,
                'currency' => $currency,
            ]
        ));
        
        $aggregate->persist();
        
        // Calculate total with proper currency
        $total = $this->calculateOrderTotal($session->cart_items, $currency);
        
        return [
            'order_uuid' => $sessionUuid,
            'order_id' => $sessionUuid,
            'status' => 'confirmed',
            'total' => $total->getAmount(),
            'formatted_total' => $total->format(),
            'currency' => $currency,
        ];
    }
    
    /**
     * Calculate order total from cart items
     */
    private function calculateOrderTotal(array $cartItems, string $currency = 'CLP'): Money
    {
        if (empty($cartItems) || !$this->itemRepository) {
            return new Money(0, new Currency($currency));
        }
        
        $itemIds = array_column($cartItems, 'id');
        $items = $this->itemRepository->getMultipleItemDetails($itemIds);
        
        $total = new Money(0, new Currency($currency));
        foreach ($cartItems as $cartItem) {
            $item = $items[$cartItem['id']] ?? null;
            if ($item) {
                // Handle both array and object formats
                $price = is_array($item) 
                    ? ($item['salePrice'] ?? $item['basePrice'])
                    : ($item->salePrice ?? $item->basePrice);
                $itemTotal = new Money($price * $cartItem['quantity'], new Currency($currency));
                $total = $total->add($itemTotal);
            }
        }
        
        return $total;
    }
}