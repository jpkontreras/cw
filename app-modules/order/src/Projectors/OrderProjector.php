<?php

declare(strict_types=1);

namespace Colame\Order\Projectors;

use Colame\Order\Events\{
    OrderStarted,
    OrderStatusChanged,
    ItemAddedToOrder,
    ItemRemovedFromOrder,
    OrderConfirmed,
    OrderCancelled,
    OrderSlipPrinted,
    SlipScannedReady,
    CustomerInfoEntered
};
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderItem;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Illuminate\Support\Facades\DB;

final class OrderProjector extends Projector
{
    public function onOrderStarted(OrderStarted $event): void
    {
        // For backward compatibility: use aggregate UUID if sessionId is not set
        // The aggregate UUID IS the session ID for OrderSession aggregates
        $sessionId = $event->sessionId ?? $event->aggregateRootUuid();

        // Get customer data from the event itself (new way) or from previous events (backward compatibility)
        $customerData = [];

        // First check if the event has customer info directly (new events)
        if (!empty($event->customerInfo)) {
            if (isset($event->customerInfo['name'])) {
                $customerData['customer_name'] = $event->customerInfo['name'];
            }
            if (isset($event->customerInfo['phone'])) {
                $customerData['customer_phone'] = $event->customerInfo['phone'];
            }
            if (isset($event->customerInfo['email'])) {
                $customerData['customer_email'] = $event->customerInfo['email'];
            }
        } else {
            // Fallback: Check for customer info from a previous CustomerInfoEntered event (backward compatibility)
            $customerInfoEvent = \Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent::where('aggregate_uuid', $sessionId)
                ->where('event_class', CustomerInfoEntered::class)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($customerInfoEvent) {
                $fields = $customerInfoEvent->event_properties['fields'] ?? [];
                if (isset($fields['name'])) {
                    $customerData['customer_name'] = $fields['name'];
                }
                if (isset($fields['phone'])) {
                    $customerData['customer_phone'] = $fields['phone'];
                }
                if (isset($fields['email'])) {
                    $customerData['customer_email'] = $fields['email'];
                }
            }
        }

        Order::create(array_merge([
            'id' => $event->orderId,
            'session_id' => $sessionId,
            'order_number' => $event->orderNumber,
            'user_id' => $event->userId, // Staff member who created the order
            'location_id' => $event->locationId,
            'type' => $event->type,
            'status' => 'started',
            'started_at' => $event->startedAt,
        ], $customerData));
    }
    
    public function onItemAddedToOrder(ItemAddedToOrder $event): void
    {
        // Get currency configuration for proper conversion
        $order = Order::find($event->orderId);
        $currency = $order->currency ?? 'CLP';
        $subunit = $this->getCurrencySubunit($currency);
        
        // Use DB directly to bypass any model issues
        // Convert to minor units based on currency (CLP: subunit=1, USD: subunit=100)
        DB::table('order_items')->insert([
            'id' => $event->lineItemId,
            'order_id' => $event->orderId,
            'item_id' => $event->itemId,
            'item_name' => $event->itemName,
            'base_price' => (int) round($event->basePrice * $subunit),  // Convert to minor units
            'unit_price' => (int) round($event->unitPrice * $subunit),  // Convert to minor units
            'quantity' => $event->quantity,
            'modifiers' => json_encode($event->modifiers),
            'notes' => $event->notes,
            'total_price' => (int) round($event->unitPrice * $event->quantity * $subunit), // Convert to minor units
            'status' => 'pending',
            'kitchen_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Update order totals
        $this->updateOrderTotals($event->orderId);
    }
    
    public function onItemRemovedFromOrder(ItemRemovedFromOrder $event): void
    {
        OrderItem::where('id', $event->lineItemId)->delete();
        
        // Update order totals
        $this->updateOrderTotals($event->orderId);
    }
    
    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        // Get currency configuration for proper conversion
        $order = Order::find($event->orderId);
        $currency = $order->currency ?? 'CLP';
        $subunit = $this->getCurrencySubunit($currency);
        
        Order::where('id', $event->orderId)->update([
            'status' => 'confirmed',
            'payment_method' => $event->paymentMethod,
            'subtotal' => (int) round($event->subtotal * $subunit), // Convert to minor units
            'tax' => (int) round($event->tax * $subunit),           // Convert to minor units
            'tip' => (int) round($event->tip * $subunit),           // Convert to minor units
            'total' => (int) round($event->total * $subunit),       // Convert to minor units
            'confirmed_at' => $event->confirmedAt,
        ]);
    }
    
    public function onOrderCancelled(OrderCancelled $event): void
    {
        Order::where('id', $event->orderId)->update([
            'status' => 'cancelled',
            'cancelled_at' => $event->cancelledAt,
            'cancelled_reason' => $event->reason,
            'cancelled_by' => $event->cancelledBy,
        ]);
    }
    
    public function onOrderStatusChanged(OrderStatusChanged $event): void
    {
        $updateData = [
            'status' => $event->toStatus,
        ];
        
        // Update timestamps based on status
        switch ($event->toStatus) {
            case 'placed':
                $updateData['placed_at'] = $event->changedAt;
                break;
            case 'confirmed':
                $updateData['confirmed_at'] = $event->changedAt;
                break;
            case 'preparing':
                $updateData['preparing_at'] = $event->changedAt;
                break;
            case 'ready':
                $updateData['ready_at'] = $event->changedAt;
                break;
            case 'delivering':
                $updateData['delivering_at'] = $event->changedAt;
                break;
            case 'delivered':
                $updateData['delivered_at'] = $event->changedAt;
                break;
            case 'completed':
                $updateData['completed_at'] = $event->changedAt;
                break;
            case 'cancelled':
                $updateData['cancelled_at'] = $event->changedAt;
                $updateData['cancellation_reason'] = $event->reason ?? 'Status change';
                break;
        }
        
        Order::where('id', $event->orderId)->update($updateData);
    }
    
    private function updateOrderTotals(string $orderId): void
    {
        $items = OrderItem::where('order_id', $orderId)->get();
        $subtotal = $items->sum('total_price'); // Already in minor units from items
        $tax = (int) round($subtotal * 0.19); // Calculate tax in minor units (Chilean IVA)
        
        Order::where('id', $orderId)->update([
            'subtotal' => $subtotal,      // Already in minor units
            'tax' => $tax,                // Now in minor units
            'total' => $subtotal + $tax,  // Both in minor units
        ]);
    }
    
    public function onOrderSlipPrinted(OrderSlipPrinted $event): void
    {
        Order::where('id', $event->orderId)->update([
            'slip_printed' => true,
            'printed_at' => $event->printedAt,
        ]);
    }

    public function onSlipScannedReady(SlipScannedReady $event): void
    {
        Order::where('id', $event->orderId)->update([
            'status' => 'ready',
            'kitchen_status' => 'completed',
            'ready_at' => $event->scannedAt,
        ]);
    }

    public function onCustomerInfoEntered(CustomerInfoEntered $event): void
    {
        // Extract customer fields from the event
        $fields = $event->fields;

        // Find the order by session_id
        $order = Order::where('session_id', $event->sessionId)->first();

        if ($order) {
            // Update the order with customer information
            $updateData = [];

            if (isset($fields['name'])) {
                $updateData['customer_name'] = $fields['name'];
            }

            if (isset($fields['phone'])) {
                $updateData['customer_phone'] = $fields['phone'];
            }

            if (isset($fields['email'])) {
                $updateData['customer_email'] = $fields['email'];
            }

            if (isset($fields['address'])) {
                $updateData['delivery_address'] = $fields['address'];
            }

            if (isset($fields['notes'])) {
                $updateData['notes'] = $fields['notes'];
            }

            if (!empty($updateData)) {
                Order::where('id', $order->id)->update($updateData);
            }
        }
    }

    /**
     * Get currency subunit multiplier from config
     * CLP has subunit=1 (no cents), USD has subunit=100 (cents)
     */
    private function getCurrencySubunit(string $currency): int
    {
        return config("money.currencies.{$currency}.subunit", 100);
    }
}