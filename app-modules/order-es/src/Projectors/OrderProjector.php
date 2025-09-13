<?php

declare(strict_types=1);

namespace Colame\OrderEs\Projectors;

use Colame\OrderEs\Events\{
    OrderStarted,
    ItemAddedToOrder,
    ItemRemovedFromOrder,
    OrderConfirmed,
    OrderCancelled
};
use Colame\OrderEs\ReadModels\Order;
use Colame\OrderEs\ReadModels\OrderItem;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

final class OrderProjector extends Projector
{
    public function onOrderStarted(OrderStarted $event): void
    {
        Order::create([
            'id' => $event->orderId,
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
        // Use DB directly to bypass any model issues
        \DB::table('order_es_order_items')->insert([
            'id' => $event->lineItemId,
            'order_id' => $event->orderId,
            'item_id' => $event->itemId,
            'item_name' => $event->itemName,
            'base_price' => (int) ($event->basePrice),
            'unit_price' => (int) ($event->unitPrice),
            'quantity' => $event->quantity,
            'modifiers' => json_encode($event->modifiers),
            'notes' => $event->notes,
            'total_price' => (int) ($event->unitPrice * $event->quantity),
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
        Order::where('id', $event->orderId)->update([
            'status' => 'confirmed',
            'payment_method' => $event->paymentMethod,
            'subtotal' => $event->subtotal,
            'tax' => $event->tax,
            'tip' => $event->tip,
            'total' => $event->total,
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
    
    private function updateOrderTotals(string $orderId): void
    {
        $items = OrderItem::where('order_id', $orderId)->get();
        $subtotal = $items->sum('total_price');
        $tax = $subtotal * 0.19; // Chilean IVA
        
        Order::where('id', $orderId)->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
        ]);
    }
}