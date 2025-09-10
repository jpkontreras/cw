<?php

declare(strict_types=1);

namespace Colame\Order\Services;

use Colame\Order\Aggregates\OrderAggregate;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\UpdateOrderData;
use Colame\Order\Data\ModifyOrderData;
use Colame\Order\Models\Order;
use Colame\Order\Exceptions\OrderNotFoundException;
use Colame\Order\Exceptions\InvalidOrderStateException;
use Colame\Location\Contracts\LocationRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Akaunting\Money\Money;
use Akaunting\Money\Currency;

/**
 * Event-sourced order service for creation and modification
 * Now with automatic state progression and process tracking
 */
class EventSourcedOrderService
{
    private const PROCESS_CACHE_PREFIX = 'order_process:';
    private const PROCESS_TTL = 3600; // 1 hour cache for process tracking
    
    public function __construct(
        private ?LocationRepositoryInterface $locationRepository = null
    ) {}
    /**
     * Create a new order using event sourcing
     * Note: Location should already be locked in the session
     */
    public function createOrder(CreateOrderData $data): string
    {
        // Location is now passed from session, not fetched from user context
        // This ensures consistency throughout the order lifecycle
        $locationId = $data->sessionLocationId ?? null;
        
        // Validate we have a location from session
        if (!$locationId) {
            throw new \InvalidArgumentException('Order session must have a locked location. Please start a new session.');
        }
        
        // Generate a new UUID for the order
        $orderUuid = Str::uuid()->toString();
        
        // Get location currency for this order
        $currency = $this->getLocationCurrency($locationId);
        
        // Initialize process tracking
        $this->initializeProcessTracking($orderUuid, [
            'type' => 'create_order',
            'user_id' => $data->userId,
            'location_id' => $locationId,
            'currency' => $currency,
        ]);
        
        // Create the aggregate and start the order
        $aggregate = OrderAggregate::retrieve($orderUuid)
            ->startOrder(
                staffId: (string) $data->userId,
                locationId: (string) $locationId,
                tableNumber: $data->tableNumber, // laravel-data handles the casting
                metadata: array_merge($data->metadata ?? [], [
                    'type' => $data->type,
                    'currency' => $currency, // Store currency in metadata
                    'customer_name' => $data->customerName,
                    'customer_phone' => $data->customerPhone,
                    'customer_email' => $data->customerEmail,
                    'delivery_address' => $data->deliveryAddress,
                    'notes' => $data->notes,
                    'special_instructions' => $data->specialInstructions,
                ])
            );
        
        // Add items to the order
        if ($data->items && count($data->items) > 0) {
            $items = [];
            foreach ($data->items as $item) {
                $items[] = [
                    'item_id' => $item->itemId,
                    'name' => $item->name ?? null,  // Include item name
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unitPrice,
                    'notes' => $item->notes ?? null,
                    'modifiers' => $item->modifiers ?? [],
                    'metadata' => $item->metadata ?? [],
                ];
            }
            
            $aggregate->addItems($items);
            
            // Don't auto-progress during creation - wait for explicit confirmation
            // This prevents state conflicts when adding more items later
        }
        
        // Persist the aggregate (saves all events)
        $aggregate->persist();
        
        $this->updateProcessTracking($orderUuid, ['status' => 'completed']);
        
        return $orderUuid;
    }
    
    /**
     * Modify an existing order (add/remove/update items)
     */
    public function modifyOrder(string $orderUuid, ModifyOrderData $data): void
    {
        $this->updateProcessTracking($orderUuid, [
            'type' => 'modify_order',
            'modified_by' => $data->modifiedBy,
        ]);
        
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Modify items if provided
        if ($data->hasModifications()) {
            $aggregate->modifyItems(
                toAdd: $data->itemsToAdd ?? [],
                toRemove: $data->itemsToRemove ?? [],
                toModify: $data->itemsToModify ?? [],
                modifiedBy: $data->modifiedBy,
                reason: $data->reason
            );
            
            // Don't auto-progress on modifications - let confirmation handle it
            // This prevents overwriting items when adding more
        }
        
        // Adjust price if needed
        if ($data->priceAdjustment) {
            $aggregate->adjustPrice(
                adjustmentType: $data->priceAdjustment['type'],
                amount: $this->createMoney($data->priceAdjustment['amount'], $orderUuid),
                reason: $data->priceAdjustment['reason'],
                authorizedBy: $data->modifiedBy
            );
        }
        
        $aggregate->persist();
        
        $this->updateProcessTracking($orderUuid, ['status' => 'modified']);
    }
    
    /**
     * Add items to an existing order
     * This is used by the action recorder and API flows
     */
    public function addItemsToOrder(string $orderUuid, array $items): void
    {
        $this->updateProcessTracking($orderUuid, [
            'type' => 'add_items',
            'items_count' => count($items),
        ]);
        
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Format items for the aggregate
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'item_id' => $item['item_id'] ?? $item['itemId'],
                'name' => $item['name'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'] ?? $item['unitPrice'] ?? 0,
                'notes' => $item['notes'] ?? null,
                'modifiers' => $item['modifiers'] ?? [],
                'metadata' => $item['metadata'] ?? [],
            ];
        }
        
        $aggregate->addItems($formattedItems);
        
        // Only persist items addition, don't auto-progress yet
        // Auto-progression should happen when confirming the order
        $aggregate->persist();
        
        $this->updateProcessTracking($orderUuid, ['status' => 'items_added']);
    }
    
    /**
     * Confirm an order (ready for kitchen)
     * This is where we progress through all validation states
     */
    public function confirmOrder(string $orderUuid, string $paymentMethod): void
    {
        $this->updateProcessTracking($orderUuid, [
            'type' => 'confirm_order',
            'payment_method' => $paymentMethod,
        ]);
        
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Check current order state from database
        $order = Order::find($orderUuid);
        $currentStatus = $order ? $order->status : null;
        
        // Progress through ALL validation states when confirming
        // This ensures proper state progression without conflicts
        if (!in_array($currentStatus, ['confirmed'])) {
            // Always run the full progression when confirming
            $this->progressOrderToConfirmation($aggregate, $orderUuid);
        }
        
        // Set payment method and confirm
        $aggregate->setPaymentMethod($paymentMethod);
        $aggregate->confirmOrder();
        
        $aggregate->persist();
        
        $this->updateProcessTracking($orderUuid, ['status' => 'confirmed']);
    }
    
    /**
     * Compatibility method - redirects to addItemsToOrder
     */
    public function addItems(string $orderUuid, array $items): void
    {
        $this->addItemsToOrder($orderUuid, $items);
    }
    
    /**
     * Apply a promotion to the order
     */
    public function applyPromotion(string $orderUuid, int $promotionId): void
    {
        $this->updateProcessTracking($orderUuid, [
            'type' => 'apply_promotion',
            'promotion_id' => $promotionId,
        ]);
        
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // In real implementation, would fetch promotion details
        // For now, apply a simple discount (10% or fixed amount based on promotion)
        $discountAmount = 100000; // Example: 1000.00 in cents
        $aggregate->applyPromotion(
            (string) $promotionId,
            $this->createMoney($discountAmount, $orderUuid)
        );
        
        $aggregate->persist();
        
        $this->updateProcessTracking($orderUuid, ['status' => 'promotion_applied']);
    }
    
    /**
     * Add tip to the order
     */
    public function addTip(string $orderUuid, float $amount, ?float $percentage = null): void
    {
        $this->updateProcessTracking($orderUuid, [
            'type' => 'add_tip',
            'tip_amount' => $amount,
            'tip_percentage' => $percentage,
        ]);
        
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        $aggregate->addTip(
            $this->createMoney((int) ($amount * 100), $orderUuid), // Convert to cents
            $percentage
        );
        
        $aggregate->persist();
        
        $this->updateProcessTracking($orderUuid, ['status' => 'tip_added']);
    }
    
    /**
     * Update customer information
     */
    public function updateCustomerInfo(string $orderUuid, array $customerData): void
    {
        $this->updateProcessTracking($orderUuid, [
            'type' => 'update_customer',
            'customer_data' => $customerData,
        ]);
        
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Update customer info through metadata
        $aggregate->updateMetadata([
            'customer_name' => $customerData['customerName'] ?? null,
            'customer_phone' => $customerData['customerPhone'] ?? null,
            'customer_email' => $customerData['customerEmail'] ?? null,
            'special_instructions' => $customerData['specialInstructions'] ?? null,
        ]);
        
        $aggregate->persist();
        
        $this->updateProcessTracking($orderUuid, ['status' => 'customer_updated']);
    }
    
    /**
     * Cancel an order
     */
    public function cancelOrder(string $orderUuid, string $reason, string $cancelledBy = ''): void
    {
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Add cancellation metadata
        $fullReason = $cancelledBy 
            ? sprintf('%s (Cancelled by: %s)', $reason, $cancelledBy)
            : $reason;
        
        $aggregate->cancelOrder($fullReason);
        
        $aggregate->persist();
    }
    
    /**
     * Get order data from projection
     */
    public function getOrder(string $orderUuid): ?OrderData
    {
        $order = Order::find($orderUuid);
        
        if (!$order) {
            return null;
        }
        
        return OrderData::from($order);
    }
    
    /**
     * Check if user can modify the order
     */
    public function canModifyOrder(string $orderUuid, int $userId): bool
    {
        $order = Order::find($orderUuid);
        
        if (!$order) {
            return false;
        }
        
        // Check status allows modification
        if (!in_array($order->status, ['draft', 'started', 'placed', 'confirmed'])) {
            return false;
        }
        
        // Check user permissions (simplified for now)
        // In real implementation, would check roles/permissions
        return true;
    }
    
    /**
     * Get modification permissions for an order
     */
    public function getModificationPermissions(string $orderUuid, int $userId): array
    {
        $order = Order::find($orderUuid);
        
        if (!$order) {
            return [
                'canModify' => false,
                'canAddItems' => false,
                'canRemoveItems' => false,
                'canAdjustPrice' => false,
                'canCancel' => false,
            ];
        }
        
        // Determine permissions based on order status and user role
        $permissions = [
            'canModify' => false,
            'canAddItems' => false,
            'canRemoveItems' => false,
            'canAdjustPrice' => false,
            'canCancel' => false,
            'requiresAuthorization' => false,
        ];
        
        switch ($order->status) {
            case 'draft':
            case 'started':
            case 'placed':
                $permissions['canModify'] = true;
                $permissions['canAddItems'] = true;
                $permissions['canRemoveItems'] = true;
                $permissions['canAdjustPrice'] = true;
                $permissions['canCancel'] = true;
                break;
                
            case 'confirmed':
                $permissions['canModify'] = true;
                $permissions['canAddItems'] = true;
                $permissions['canRemoveItems'] = false; // Kitchen already started
                $permissions['canAdjustPrice'] = true;
                $permissions['canCancel'] = true;
                $permissions['requiresAuthorization'] = true;
                break;
                
            case 'preparing':
                $permissions['canModify'] = true;
                $permissions['canAddItems'] = true; // Only additions
                $permissions['canRemoveItems'] = false;
                $permissions['canAdjustPrice'] = true;
                $permissions['canCancel'] = false;
                $permissions['requiresAuthorization'] = true;
                break;
                
            default:
                // No modifications allowed
                break;
        }
        
        return $permissions;
    }
    
    // Private helper methods
    
    private function validateItems($aggregate): array
    {
        // In real implementation, would validate with item service
        // For now, return the items as-is
        return $aggregate->getItems();
    }
    
    private function calculateSubtotal(array $items, string $orderUuid): Money
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }
        
        return $this->createMoney($total, $orderUuid);
    }
    
    private function calculatePromotions($aggregate): array
    {
        // In real implementation, would call promotion service
        return [
            'available' => [],
            'auto_applied' => [],
        ];
    }
    
    private function calculateTax($aggregate, string $orderUuid): Money
    {
        // Tax rate should ideally come from location settings
        // Default to 19% for Chilean IVA, but this should be configurable
        $subtotal = $aggregate->getTotal()->getAmount();
        $taxRate = 0.19; // TODO: Get from location settings
        $tax = (int) round($subtotal * $taxRate);
        
        return $this->createMoney($tax, $orderUuid);
    }
    
    private function calculateTotal($aggregate, Money $tax): Money
    {
        $subtotal = $aggregate->getTotal();
        return $subtotal->add($tax);
    }
    
    // NEW: Progression methods
    
    /**
     * Progress order to confirmation state
     * Runs all validation steps in sequence
     */
    private function progressOrderToConfirmation(OrderAggregate $aggregate, string $orderUuid): void
    {
        try {
            // Get current state to avoid duplicate events
            $order = Order::find($orderUuid);
            $currentStatus = $order ? $order->status : null;
            
            // Step 1: Validate items (if not done)
            if (!in_array($currentStatus, ['items_validated', 'promotions_calculated', 'price_calculated'])) {
                $this->updateProcessTracking($orderUuid, ['step' => 'validating_items']);
                $validatedItems = $this->validateItems($aggregate);
                
                // Debug: Log what we're passing to validation
                Log::info("Items being validated", [
                    'orderUuid' => $orderUuid,
                    'validatedItems' => $validatedItems
                ]);
                
                $subtotal = $this->calculateSubtotal($validatedItems, $orderUuid);
                $aggregate->markItemsAsValidated($validatedItems, $subtotal);
            }
            
            // Step 2: Calculate promotions (if not done)
            if (!in_array($currentStatus, ['promotions_calculated', 'price_calculated'])) {
                $this->updateProcessTracking($orderUuid, ['step' => 'calculating_promotions']);
                $promotions = $this->calculatePromotions($aggregate);
                $aggregate->setPromotions(
                    $promotions['available'],
                    $promotions['auto_applied']
                );
            }
            
            // Step 3: Calculate final price (if not done)
            if ($currentStatus !== 'price_calculated') {
                $this->updateProcessTracking($orderUuid, ['step' => 'calculating_price']);
                $tax = $this->calculateTax($aggregate, $orderUuid);
                $total = $this->calculateTotal($aggregate, $tax);
                $aggregate->calculateFinalPrice($tax, $total);
            }
            
            $this->updateProcessTracking($orderUuid, ['step' => 'progression_complete']);
            
            Log::info("Order {$orderUuid} progressed through validation states for confirmation");
        } catch (\Exception $e) {
            Log::error("Failed to progress order {$orderUuid}: {$e->getMessage()}");
            $this->updateProcessTracking($orderUuid, [
                'step' => 'progression_failed',
                'error' => $e->getMessage(),
            ]);
            throw $e; // On confirmation, we should throw to prevent incomplete orders
        }
    }
    
    /**
     * Initialize process tracking for an order
     */
    private function initializeProcessTracking(string $orderUuid, array $metadata = []): void
    {
        $processData = array_merge([
            'order_uuid' => $orderUuid,
            'started_at' => now()->toIso8601String(),
            'status' => 'in_progress',
            'steps' => [],
        ], $metadata);
        
        Cache::put(
            self::PROCESS_CACHE_PREFIX . $orderUuid,
            $processData,
            self::PROCESS_TTL
        );
    }
    
    /**
     * Update process tracking for an order
     */
    private function updateProcessTracking(string $orderUuid, array $updates): void
    {
        $key = self::PROCESS_CACHE_PREFIX . $orderUuid;
        $current = Cache::get($key, []);
        
        // Add timestamp to steps
        if (isset($updates['step'])) {
            $current['steps'][] = [
                'name' => $updates['step'],
                'timestamp' => now()->toIso8601String(),
            ];
            unset($updates['step']);
        }
        
        $updated = array_merge($current, $updates);
        $updated['updated_at'] = now()->toIso8601String();
        
        Cache::put($key, $updated, self::PROCESS_TTL);
    }
    
    /**
     * Get process tracking data for an order
     */
    public function getProcessTracking(string $orderUuid): ?array
    {
        return Cache::get(self::PROCESS_CACHE_PREFIX . $orderUuid);
    }
    
    /**
     * Get the currency for an order based on its location
     */
    private function getOrderCurrency(string $orderUuid): string
    {
        $order = Order::find($orderUuid);
        
        if (!$order || !$order->location_id) {
            // Fallback to config default or CLP for Chilean system
            return config('money.defaults.currency', 'CLP');
        }
        
        return $this->getLocationCurrency($order->location_id);
    }
    
    /**
     * Get currency for a specific location
     * Note: This should ideally be cached from session to avoid repeated lookups
     */
    private function getLocationCurrency(int $locationId): string
    {
        // Check if we have currency in session cache first
        $sessionCurrency = Cache::get("order_session_currency:{$locationId}");
        if ($sessionCurrency) {
            return $sessionCurrency;
        }
        
        // Otherwise fetch from repository if available
        if ($this->locationRepository) {
            $location = $this->locationRepository->find($locationId);
            if (!$location) {
                throw new \InvalidArgumentException("Location with ID {$locationId} not found");
            }
            $currency = $location->currency ?? config('money.defaults.currency', 'CLP');
            
            // Cache for this session
            Cache::put("order_session_currency:{$locationId}", $currency, now()->addHours(24));
            return $currency;
        }
        
        // Fallback to config default if no repository available
        return config('money.defaults.currency', 'CLP');
    }
    
    /**
     * Create a Money instance with the correct currency for the order
     */
    private function createMoney(int $amount, string $orderUuid): Money
    {
        $currency = $this->getOrderCurrency($orderUuid);
        return new Money($amount, new Currency($currency));
    }
}