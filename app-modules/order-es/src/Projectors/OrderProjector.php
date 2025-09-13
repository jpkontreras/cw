<?php

declare(strict_types=1);

namespace Colame\OrderEs\Projectors;

use Colame\OrderEs\Events\{
    OrderStarted,
    OrderStatusChanged,
    ItemAddedToOrder,
    ItemRemovedFromOrder,
    OrderConfirmed,
    OrderCancelled
};
use Colame\OrderEs\Models\Order;
use Colame\OrderEs\Models\OrderItem;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Illuminate\Support\Facades\DB;

final class OrderProjector extends Projector
{
    public function onOrderStarted(OrderStarted $event): void
    {
        // For backward compatibility: use aggregate UUID if sessionId is not set
        // The aggregate UUID IS the session ID for OrderSession aggregates
        $sessionId = $event->sessionId ?? $event->aggregateRootUuid();
        
        Order::create([
            'id' => $event->orderId,
            'session_id' => $sessionId,
            'order_number' => $event->orderNumber,
            'user_id' => $event->userId, // Staff member who created the order
            'location_id' => $event->locationId,
            'type' => $event->type,
            'status' => 'started',
            'started_at' => $event->startedAt,
        ]);
    }
    
    public function onItemAddedToOrder(ItemAddedToOrder $event): void
    {
        // Get currency configuration for proper conversion
        $order = Order::find($event->orderId);
        $currency = $order->currency ?? 'CLP';
        $subunit = $this->getCurrencySubunit($currency);
        
        // Use DB directly to bypass any model issues
        // Convert to minor units based on currency (CLP: subunit=1, USD: subunit=100)
        DB::table('order_es_order_items')->insert([
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
    
    /**
     * Get currency subunit multiplier from config
     * CLP has subunit=1 (no cents), USD has subunit=100 (cents)
     */
    private function getCurrencySubunit(string $currency): int
    {
        return config("money.currencies.{$currency}.subunit", 100);
    }
}