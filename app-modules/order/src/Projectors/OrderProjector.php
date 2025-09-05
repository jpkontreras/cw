<?php

namespace Colame\Order\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Events\OrderStarted;
use Colame\Order\Events\ItemsAddedToOrder;
use Colame\Order\Events\ItemsValidated;
use Colame\Order\Events\PromotionsCalculated;
use Colame\Order\Events\PromotionApplied;
use Colame\Order\Events\PromotionRemoved;
use Colame\Order\Events\PriceCalculated;
use Colame\Order\Events\TipAdded;
use Colame\Order\Events\PaymentMethodSet;
use Colame\Order\Events\OrderConfirmed;
use Colame\Order\Events\OrderCancelled;
use Colame\Order\Events\ItemsModified;
use Colame\Order\Events\PriceAdjusted;
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderItem;
use Colame\Order\Models\OrderPromotion;
use Illuminate\Support\Facades\DB;

class OrderProjector extends Projector
{
    public function onOrderStarted(OrderStarted $event): void
    {
        Order::create([
            'uuid' => $event->aggregateRootUuid,
            'staff_id' => $event->staffId,
            'location_id' => $event->locationId,
            'table_number' => $event->tableNumber,
            'status' => 'started',
            'metadata' => $event->metadata,
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'tip' => 0,
            'total' => 0,
        ]);
    }

    public function onItemsAddedToOrder(ItemsAddedToOrder $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            foreach ($event->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] ?? 0,
                    'modifiers' => $item['modifiers'] ?? [],
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending_validation',
                ]);
            }

            $order->update(['status' => 'items_added']);
        });
    }

    public function onItemsValidated(ItemsValidated $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Update order items with validated data
            OrderItem::where('order_id', $order->id)
                ->update(['status' => 'validated']);

            // Update validated items with actual prices and details
            foreach ($event->validatedItems as $item) {
                OrderItem::where('order_id', $order->id)
                    ->where('item_id', $item['item_id'])
                    ->update([
                        'price' => $item['price'],
                        'name' => $item['name'],
                        'category' => $item['category'] ?? null,
                    ]);
            }

            $order->update([
                'status' => 'items_validated',
                'subtotal' => $event->subtotal,
            ]);
        });
    }

    public function onPromotionsCalculated(PromotionsCalculated $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Store available promotions
            $order->update([
                'available_promotions' => $event->availablePromotions,
                'status' => 'promotions_calculated',
            ]);

            // Apply auto-applied promotions
            foreach ($event->autoApplied as $promotion) {
                OrderPromotion::create([
                    'order_id' => $order->id,
                    'promotion_id' => $promotion['id'],
                    'discount_amount' => $promotion['discount_amount'],
                    'type' => $promotion['type'],
                    'auto_applied' => true,
                ]);
            }

            $order->update(['discount' => $event->totalDiscount]);
        });
    }

    public function onPromotionApplied(PromotionApplied $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        OrderPromotion::create([
            'order_id' => $order->id,
            'promotion_id' => $event->promotionId,
            'discount_amount' => $event->discountAmount,
            'auto_applied' => false,
        ]);

        $order->increment('discount', $event->discountAmount);
    }

    public function onPromotionRemoved(PromotionRemoved $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        $promotion = OrderPromotion::where('order_id', $order->id)
            ->where('promotion_id', $event->promotionId)
            ->first();

        if ($promotion) {
            $order->decrement('discount', $promotion->discount_amount);
            $promotion->delete();
        }
    }

    public function onPriceCalculated(PriceCalculated $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        $order->update([
            'subtotal' => $event->subtotal,
            'discount' => $event->discount,
            'tax' => $event->tax,
            'tip' => $event->tip,
            'total' => $event->total,
            'status' => 'price_calculated',
        ]);
    }

    public function onTipAdded(TipAdded $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        $order->update([
            'tip' => $event->tipAmount,
            'total' => $order->total - $order->tip + $event->tipAmount,
        ]);
    }

    public function onPaymentMethodSet(PaymentMethodSet $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        $order->update([
            'payment_method' => $event->paymentMethod,
        ]);
    }

    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        $order->update([
            'order_number' => $event->orderNumber,
            'status' => 'confirmed',
            'confirmed_at' => $event->confirmedAt,
        ]);

        // Emit event for other modules to listen to
        event(new \Colame\Order\Events\OrderConfirmedForKitchen(
            orderId: $order->id,
            locationId: $order->location_id,
            items: $order->items->toArray()
        ));
    }

    public function onOrderCancelled(OrderCancelled $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $event->reason,
            'cancelled_at' => $event->cancelledAt,
        ]);
    }

    public function onItemsModified(ItemsModified $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Handle removed items
            if (!empty($event->removedItems)) {
                OrderItem::where('order_id', $order->id)
                    ->whereIn('item_id', $event->removedItems)
                    ->delete();
            }
            
            // Handle modified items
            foreach ($event->modifiedItems as $modification) {
                OrderItem::where('order_id', $order->id)
                    ->where('item_id', $modification['item_id'])
                    ->update([
                        'quantity' => $modification['quantity'] ?? null,
                        'notes' => $modification['notes'] ?? null,
                        'modifiers' => $modification['modifiers'] ?? null,
                        'total_price' => ($modification['quantity'] ?? 1) * ($modification['unit_price'] ?? 0),
                    ]);
            }
            
            // Handle added items
            foreach ($event->addedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item['item_id'],
                    'item_name' => $item['name'] ?? 'Item ' . $item['item_id'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_price' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
                    'notes' => $item['notes'] ?? null,
                    'modifiers' => $item['modifiers'] ?? [],
                    'status' => 'pending',
                    'kitchen_status' => 'pending',
                ]);
            }
            
            // Update order totals and metadata
            $order->update([
                'total' => $event->newTotal,
                'last_modified_at' => $event->modifiedAt,
                'last_modified_by' => $event->modifiedBy,
                'modification_count' => ($order->modification_count ?? 0) + 1,
            ]);
            
            // Store modification history in metadata
            $metadata = $order->metadata ?? [];
            $metadata['modifications'] = $metadata['modifications'] ?? [];
            $metadata['modifications'][] = [
                'timestamp' => $event->modifiedAt->toISOString(),
                'modified_by' => $event->modifiedBy,
                'reason' => $event->reason,
                'added_count' => count($event->addedItems),
                'removed_count' => count($event->removedItems),
                'modified_count' => count($event->modifiedItems),
                'amount_difference' => $event->getAmountDifference(),
            ];
            $order->metadata = $metadata;
            $order->save();
        });
    }

    public function onPriceAdjusted(PriceAdjusted $event): void
    {
        $order = Order::where('uuid', $event->aggregateRootUuid)->first();
        
        if (!$order) {
            return;
        }
        
        // Update order based on adjustment type
        switch ($event->adjustmentType) {
            case 'discount':
                $order->discount = ($order->discount ?? 0) + $event->amount;
                $order->total = $order->total - $event->amount;
                break;
                
            case 'surcharge':
                $order->total = $order->total + $event->amount;
                break;
                
            case 'correction':
                // Direct total replacement
                $order->total = $event->amount;
                break;
                
            case 'tip':
                $order->tip = ($order->tip ?? 0) + $event->amount;
                $order->total = $order->total + $event->amount;
                break;
        }
        
        // Store adjustment history in metadata
        $metadata = $order->metadata ?? [];
        $metadata['price_adjustments'] = $metadata['price_adjustments'] ?? [];
        $metadata['price_adjustments'][] = [
            'timestamp' => $event->adjustedAt->toISOString(),
            'type' => $event->adjustmentType,
            'amount' => $event->amount,
            'reason' => $event->reason,
            'authorized_by' => $event->authorizedBy,
            'authorization_code' => $event->authorizationCode,
            'affects_payment' => $event->affectsPayment,
        ];
        $order->metadata = $metadata;
        
        $order->save();
    }
}