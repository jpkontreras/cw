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
use Illuminate\Support\Str;
use Money\Money;
use Money\Currency;

/**
 * Event-sourced order service for creation and modification
 */
class EventSourcedOrderService
{
    /**
     * Create a new order using event sourcing
     */
    public function createOrder(CreateOrderData $data): string
    {
        // Generate a new UUID for the order
        $orderUuid = Str::uuid()->toString();
        
        // Create the aggregate and start the order
        $aggregate = OrderAggregate::retrieve($orderUuid)
            ->startOrder(
                staffId: (string) $data->userId,
                locationId: (string) $data->locationId,
                tableNumber: $data->tableNumber,
                metadata: array_merge($data->metadata ?? [], [
                    'type' => $data->type,
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
            $items = $data->items->map(function ($item) {
                return [
                    'item_id' => $item->itemId,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unitPrice,
                    'notes' => $item->notes,
                    'modifiers' => $item->modifiers ?? [],
                    'metadata' => $item->metadata ?? [],
                ];
            })->toArray();
            
            $aggregate->addItems($items);
        }
        
        // Persist the aggregate (saves all events)
        $aggregate->persist();
        
        return $orderUuid;
    }
    
    /**
     * Modify an existing order (add/remove/update items)
     */
    public function modifyOrder(string $orderUuid, ModifyOrderData $data): void
    {
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
        }
        
        // Adjust price if needed
        if ($data->priceAdjustment) {
            $aggregate->adjustPrice(
                adjustmentType: $data->priceAdjustment['type'],
                amount: new Money(
                    $data->priceAdjustment['amount'],
                    new Currency('CLP')
                ),
                reason: $data->priceAdjustment['reason'],
                authorizedBy: $data->modifiedBy
            );
        }
        
        $aggregate->persist();
    }
    
    /**
     * Confirm an order (ready for kitchen)
     */
    public function confirmOrder(string $orderUuid, string $paymentMethod): void
    {
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Validate items (this would call item service to verify availability)
        $validatedItems = $this->validateItems($aggregate);
        $subtotal = $this->calculateSubtotal($validatedItems);
        
        $aggregate->markItemsAsValidated($validatedItems, $subtotal);
        
        // Calculate and apply promotions
        $promotions = $this->calculatePromotions($aggregate);
        $aggregate->setPromotions(
            $promotions['available'],
            $promotions['auto_applied']
        );
        
        // Calculate final price
        $tax = $this->calculateTax($aggregate);
        $total = $this->calculateTotal($aggregate, $tax);
        $aggregate->calculateFinalPrice($tax, $total);
        
        // Set payment method and confirm
        $aggregate->setPaymentMethod($paymentMethod);
        $aggregate->confirmOrder();
        
        $aggregate->persist();
    }
    
    /**
     * Cancel an order
     */
    public function cancelOrder(string $orderUuid, string $reason, string $cancelledBy): void
    {
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Add cancellation metadata
        $fullReason = sprintf('%s (Cancelled by: %s)', $reason, $cancelledBy);
        $aggregate->cancelOrder($fullReason);
        
        $aggregate->persist();
    }
    
    /**
     * Get order data from projection
     */
    public function getOrder(string $orderUuid): ?OrderData
    {
        $order = Order::where('uuid', $orderUuid)->first();
        
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
        $order = Order::where('uuid', $orderUuid)->first();
        
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
        $order = Order::where('uuid', $orderUuid)->first();
        
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
    
    private function calculateSubtotal(array $items): Money
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }
        
        return new Money($total, new Currency('CLP'));
    }
    
    private function calculatePromotions($aggregate): array
    {
        // In real implementation, would call promotion service
        return [
            'available' => [],
            'auto_applied' => [],
        ];
    }
    
    private function calculateTax($aggregate): Money
    {
        // Chilean IVA is 19%
        $subtotal = $aggregate->getTotal()->getAmount();
        $tax = (int) round($subtotal * 0.19);
        
        return new Money($tax, new Currency('CLP'));
    }
    
    private function calculateTotal($aggregate, Money $tax): Money
    {
        $subtotal = $aggregate->getTotal();
        return $subtotal->add($tax);
    }
}