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
use Colame\Order\Events\OrderStatusTransitioned;
use Colame\Order\Events\PaymentProcessed;
use Colame\Order\Events\PaymentFailed;
use Colame\Order\Events\CustomerInfoUpdated;
use Colame\Order\Events\OrderItemsUpdated;
use Colame\Order\Events\ItemModifiersChanged;
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderItem;
use Colame\Order\Models\OrderPromotion;
use Colame\Order\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class OrderProjector extends Projector
{
    public function onOrderStarted(OrderStarted $event): void
    {
        // Debug: Log that projector is being called
        \Illuminate\Support\Facades\Log::info("OrderProjector.onOrderStarted called", [
            'aggregateUuid' => $event->aggregateRootUuid,
            'staffId' => $event->staffId,
            'locationId' => $event->locationId
        ]);
        
        // Extract customer data from metadata
        $metadata = $event->metadata ?? [];
        
        // Generate order number immediately (ORD-XXXX format)
        $orderNumber = $this->generateOrderNumber($event->locationId);
        
        // Debug: Log before database operation
        \Illuminate\Support\Facades\Log::info("Creating order in database", [
            'id' => $event->aggregateRootUuid,
            'orderNumber' => $orderNumber,
            'locationId' => $event->locationId
        ]);
        
        $order = Order::updateOrCreate(
            ['id' => $event->aggregateRootUuid], // Use UUID as primary key
            [
                'id' => $event->aggregateRootUuid, // Ensure UUID is preserved
                'session_id' => $event->sessionId, // Store the session reference
                'order_number' => $orderNumber,
                'user_id' => $metadata['user_id'] ?? null, // User who created the order
                'waiter_id' => $event->staffId, // staffId maps to waiter_id column (nullable)
                'location_id' => $event->locationId,
                'table_number' => $event->tableNumber,
                'type' => $metadata['type'] ?? 'dine_in',
                'customer_name' => $metadata['customer_name'] ?? null,
                'customer_phone' => $metadata['customer_phone'] ?? null,
                'customer_email' => $metadata['customer_email'] ?? null,
                'delivery_address' => $metadata['delivery_address'] ?? null,
                'notes' => $metadata['notes'] ?? null,
                'special_instructions' => $metadata['special_instructions'] ?? null,
                'status' => 'started', // Will be cast to StartedState by model
                'metadata' => $event->metadata,
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'tip' => 0,
                'total' => 0,
            ]
        );
        
        // Debug: Log after database operation
        \Illuminate\Support\Facades\Log::info("Order created successfully in database", [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'created' => $order->wasRecentlyCreated
        ]);
    }

    public function onItemsAddedToOrder(ItemsAddedToOrder $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            foreach ($event->items as $item) {
                $unitPrice = $item['unit_price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item['item_id'],
                    'item_name' => $item['name'] ?? 'Item ' . $item['item_id'],
                    'base_item_name' => $item['name'] ?? null,  // Store original item name
                    // 'category' => $item['category'] ?? null, // Column doesn't exist in table
                    'quantity' => $quantity,
                    'base_price' => $unitPrice,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
                    'modifiers' => $item['modifiers'] ?? [],
                    'modifiers_total' => 0,
                    'modifier_count' => 0,
                    'modifier_history' => json_encode([]),
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending_validation',
                    'kitchen_status' => 'pending',
                ]);
            }

            $order->update(['status' => 'items_added']); // Will be cast to ItemsAddedState
        });
    }

    public function onItemsValidated(ItemsValidated $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Update order items with validated data
            OrderItem::where('order_id', $order->id)
                ->update(['status' => 'validated']);

            // Update validated items with actual prices and details
            foreach ($event->validatedItems as $item) {
                // Handle both 'price' and 'unit_price' keys for compatibility
                $price = $item['price'] ?? $item['unit_price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                
                OrderItem::where('order_id', $order->id)
                    ->where('item_id', $item['item_id'])
                    ->update([
                        'unit_price' => $price,
                        'total_price' => $quantity * $price,
                        'item_name' => $item['name'] ?? null,
                        // 'category' => $item['category'] ?? null, // Column doesn't exist in table
                    ]);
            }

            $order->update([
                'status' => 'items_validated', // Will be cast to ItemsValidatedState
                'subtotal' => $event->subtotal,
            ]);
        });
    }

    public function onPromotionsCalculated(PromotionsCalculated $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Store available promotions
            $order->update([
                'available_promotions' => $event->availablePromotions,
                'status' => 'promotions_calculated', // Will be cast to PromotionsCalculatedState
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
        $order = Order::find($event->aggregateRootUuid);

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
        $order = Order::find($event->aggregateRootUuid);

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
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        $order->update([
            'subtotal' => $event->subtotal,
            'discount' => $event->discount,
            'tax' => $event->tax,
            'tip' => $event->tip,
            'total' => $event->total,
            'status' => 'price_calculated', // Will be cast to PriceCalculatedState
        ]);
    }

    public function onTipAdded(TipAdded $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

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
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        $order->update([
            'payment_method' => $event->paymentMethod,
        ]);
    }

    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        $order->update([
            // Order number already set in onOrderStarted
            'status' => 'confirmed', // Will be cast to ConfirmedState
            'confirmed_at' => $event->confirmedAt,
        ]);

        // TODO: Emit event for other modules to listen to when OrderConfirmedForKitchen event is created
        // event(new \Colame\Order\Events\OrderConfirmedForKitchen(
        //     orderId: $order->id,
        //     locationId: $order->location_id,
        //     items: $order->items->toArray()
        // ));
    }

    public function onOrderCancelled(OrderCancelled $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        $order->update([
            'status' => 'cancelled', // Will be cast to CancelledState
            'cancellation_reason' => $event->reason,
            'cancelled_at' => $event->cancelledAt,
        ]);
    }

    public function onItemsModified(ItemsModified $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

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
                $unitPrice = $item['unit_price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item['item_id'],
                    'item_name' => $item['name'] ?? 'Item ' . $item['item_id'],
                    'quantity' => $quantity,
                    'base_price' => $unitPrice,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
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
                'timestamp' => $event->modifiedAt->toIso8601String(),
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
        $order = Order::find($event->aggregateRootUuid);

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
            'timestamp' => $event->adjustedAt->toIso8601String(),
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

    public function onOrderStatusTransitioned(OrderStatusTransitioned $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        // Store previous status for history
        $previousStatus = $order->status;

        // Update order status
        $order->status = $event->newStatus;

        // Update status timestamps
        switch ($event->newStatus) {
            case 'confirmed':
                $order->confirmed_at = $event->transitionedAt;
                break;
            case 'preparing':
                $order->preparing_at = $event->transitionedAt;
                break;
            case 'ready':
                $order->ready_at = $event->transitionedAt;
                break;
            case 'completed':
                $order->completed_at = $event->transitionedAt;
                break;
            case 'cancelled':
                $order->cancelled_at = $event->transitionedAt;
                $order->cancellation_reason = $event->reason;
                break;
        }

        // Store transition history in metadata
        $metadata = $order->metadata ?? [];
        $metadata['status_history'] = $metadata['status_history'] ?? [];
        $metadata['status_history'][] = [
            'from' => $previousStatus,
            'to' => $event->newStatus,
            'reason' => $event->reason,
            'transitioned_by' => $event->transitionedBy,
            'transitioned_at' => $event->transitionedAt->toIso8601String(),
        ];
        $order->metadata = $metadata;

        $order->save();
    }

    public function onPaymentProcessed(PaymentProcessed $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Create payment transaction record
            PaymentTransaction::create([
                'order_id' => $order->id,
                'payment_id' => $event->paymentId,
                'payment_method' => $event->paymentMethod,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'status' => $event->status,
                'transaction_id' => $event->transactionId,
                'metadata' => $event->metadata,
                'processed_at' => $event->processedAt,
            ]);

            // Update order payment status
            if ($event->isSuccessful()) {
                $order->payment_status = Order::PAYMENT_PAID;
                $order->paid_at = $event->processedAt;
            } else {
                $order->payment_status = $event->status;
            }

            // Store payment info in metadata
            $metadata = $order->metadata ?? [];
            $metadata['last_payment'] = [
                'payment_id' => $event->paymentId,
                'method' => $event->paymentMethod,
                'amount' => $event->amount,
                'status' => $event->status,
                'transaction_id' => $event->transactionId,
                'processed_at' => $event->processedAt->toIso8601String(),
            ];
            $order->metadata = $metadata;

            $order->save();
        });
    }

    public function onPaymentFailed(PaymentFailed $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Create failed payment record
            PaymentTransaction::create([
                'order_id' => $order->id,
                'payment_id' => $event->paymentId,
                'payment_method' => $event->paymentMethod,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'status' => 'failed',
                'error_message' => $event->failureReason,
                'error_code' => $event->errorCode,
                'metadata' => $event->metadata,
                'processed_at' => $event->failedAt,
            ]);

            // Update order payment status
            $order->payment_status = Order::PAYMENT_FAILED;

            // Store failure in metadata
            $metadata = $order->metadata ?? [];
            $metadata['payment_failures'] = $metadata['payment_failures'] ?? [];
            $metadata['payment_failures'][] = [
                'payment_id' => $event->paymentId,
                'method' => $event->paymentMethod,
                'reason' => $event->failureReason,
                'error_code' => $event->errorCode,
                'failed_at' => $event->failedAt->toIso8601String(),
            ];
            $order->metadata = $metadata;

            $order->save();
        });
    }

    public function onCustomerInfoUpdated(CustomerInfoUpdated $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        // Update customer information
        if ($event->customerName !== null) {
            $order->customer_name = $event->customerName;
        }
        if ($event->customerPhone !== null) {
            $order->customer_phone = $event->customerPhone;
        }
        if ($event->customerEmail !== null) {
            $order->customer_email = $event->customerEmail;
        }
        if ($event->deliveryAddress !== null) {
            $order->delivery_address = $event->deliveryAddress;
        }
        if ($event->tableNumber !== null) {
            $order->table_number = $event->tableNumber;
        }
        if ($event->notes !== null) {
            $order->notes = $event->notes;
        }
        if ($event->specialInstructions !== null) {
            $order->special_instructions = $event->specialInstructions;
        }

        // Track update history
        $metadata = $order->metadata ?? [];
        $metadata['customer_updates'] = $metadata['customer_updates'] ?? [];
        $metadata['customer_updates'][] = [
            'updated_by' => $event->updatedBy,
            'updated_at' => $event->updatedAt->toIso8601String(),
            'fields_updated' => array_filter([
                'name' => $event->customerName !== null,
                'phone' => $event->customerPhone !== null,
                'email' => $event->customerEmail !== null,
                'address' => $event->deliveryAddress !== null,
                'table' => $event->tableNumber !== null,
                'notes' => $event->notes !== null,
                'instructions' => $event->specialInstructions !== null,
            ]),
        ];
        $order->metadata = $metadata;

        $order->save();
    }

    public function onOrderItemsUpdated(OrderItemsUpdated $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Delete removed items
            if (!empty($event->deletedItemIds)) {
                OrderItem::where('order_id', $order->id)
                    ->whereIn('item_id', $event->deletedItemIds)
                    ->delete();
            }

            // Update existing items
            foreach ($event->updatedItems as $updatedItem) {
                OrderItem::where('order_id', $order->id)
                    ->where('item_id', $updatedItem['id'] ?? $updatedItem['item_id'])
                    ->update([
                        'quantity' => $updatedItem['quantity'] ?? null,
                        'unit_price' => $updatedItem['unit_price'] ?? null,
                        'total_price' => isset($updatedItem['quantity'], $updatedItem['unit_price'])
                            ? $updatedItem['quantity'] * $updatedItem['unit_price']
                            : null,
                        'notes' => $updatedItem['notes'] ?? null,
                        'modifiers' => $updatedItem['modifiers'] ?? null,
                    ]);
            }

            // Update order total
            $order->total = $event->newTotal;

            // Track update history
            $metadata = $order->metadata ?? [];
            $metadata['item_updates'] = $metadata['item_updates'] ?? [];
            $metadata['item_updates'][] = [
                'updated_by' => $event->updatedBy,
                'updated_at' => $event->updatedAt->toIso8601String(),
                'updated_count' => count($event->updatedItems),
                'deleted_count' => count($event->deletedItemIds),
                'amount_difference' => $event->getAmountDifference(),
            ];
            $order->metadata = $metadata;

            $order->save();
        });
    }

    /**
     * Handle item modifier changes
     */
    public function onItemModifiersChanged(ItemModifiersChanged $event): void
    {
        $order = Order::find($event->aggregateRootUuid);

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order, $event) {
            // Find and update the order item
            $orderItem = OrderItem::find($event->orderItemId);

            if (!$orderItem) {
                return;
            }

            // Update the order item with new modifier data
            $currentModifiers = json_decode($orderItem->modifiers ?? '[]', true);

            // Process added modifiers
            foreach ($event->addedModifiers as $modifier) {
                $currentModifiers[] = $modifier;
            }

            // Process removed modifiers
            foreach ($event->removedModifiers as $modifierId) {
                $currentModifiers = array_filter($currentModifiers, function ($mod) use ($modifierId) {
                    return ($mod['id'] ?? '') !== $modifierId;
                });
            }

            // Process updated modifiers
            foreach ($event->updatedModifiers as $updatedModifier) {
                foreach ($currentModifiers as &$modifier) {
                    if (($modifier['id'] ?? '') === ($updatedModifier['id'] ?? '')) {
                        $modifier = array_merge($modifier, $updatedModifier);
                        break;
                    }
                }
            }

            // Update order item
            $orderItem->modifiers = json_encode(array_values($currentModifiers));
            $orderItem->modifier_count = count($currentModifiers);
            $orderItem->modifiers_total = $event->newPrice - ($orderItem->base_price * $orderItem->quantity);
            $orderItem->unit_price = (int)($event->newPrice / $orderItem->quantity);
            $orderItem->total_price = $event->newPrice;
            $orderItem->modified_at = $event->modifiedAt;

            // Track modifier history
            $modifierHistory = json_decode($orderItem->modifier_history ?? '[]', true);
            $modifierHistory[] = [
                'action' => 'modified',
                'timestamp' => $event->modifiedAt->toIso8601String(),
                'modified_by' => $event->modifiedBy,
                'reason' => $event->reason,
                'added' => $event->addedModifiers,
                'removed' => $event->removedModifiers,
                'updated' => $event->updatedModifiers,
                'price_change' => $event->getPriceDifference(),
            ];
            $orderItem->modifier_history = json_encode($modifierHistory);

            $orderItem->save();

            // Create individual modifier records in order_item_modifiers table
            foreach ($event->addedModifiers as $modifier) {
                DB::table('order_item_modifiers')->insert([
                    'order_item_id' => $event->orderItemId,
                    'modifier_id' => $modifier['id'] ?? uniqid('mod_'),
                    'type' => $modifier['type'] ?? 'customization',
                    'name' => $modifier['name'] ?? '',
                    'action' => $modifier['action'] ?? 'add',
                    'quantity' => $modifier['quantity'] ?? 1,
                    'unit_price_adjustment' => $modifier['priceAdjustment'] ?? 0,
                    'total_price_adjustment' => ($modifier['priceAdjustment'] ?? 0) * ($modifier['quantity'] ?? 1),
                    'group' => $modifier['group'] ?? null,
                    'affects_kitchen' => $modifier['affectsKitchen'] ?? true,
                    'status' => 'active',
                    'metadata' => json_encode($modifier['metadata'] ?? []),
                    'added_by' => $event->modifiedBy,
                    'added_at' => $event->modifiedAt,
                ]);
            }

            // Mark removed modifiers as cancelled
            foreach ($event->removedModifiers as $modifierId) {
                DB::table('order_item_modifiers')
                    ->where('order_item_id', $event->orderItemId)
                    ->where('modifier_id', $modifierId)
                    ->update([
                        'status' => 'cancelled',
                        'modified_at' => $event->modifiedAt,
                    ]);
            }

            // Update order totals
            $order->recalculateTotals();
            $order->modification_count = ($order->modification_count ?? 0) + 1;
            $order->last_modified_at = $event->modifiedAt;
            $order->last_modified_by = $event->modifiedBy;

            // Update metadata
            $metadata = json_decode($order->metadata ?? '{}', true);
            $metadata['last_item_modification'] = [
                'item_id' => $event->orderItemId,
                'item_name' => $event->itemName,
                'timestamp' => $event->modifiedAt->toIso8601String(),
                'modified_by' => $event->modifiedBy,
                'price_change' => $event->getPriceDifference(),
            ];
            $order->metadata = json_encode($metadata);

            $order->save();

            // Notify kitchen if needed
            if ($event->requiresKitchenNotification && $orderItem->kitchen_status !== 'pending') {
                // TODO: Emit kitchen notification event
                // event(new KitchenItemModified(...));
            }
        });
    }
    
    /**
     * Generate order number with ORD- prefix
     */
    private function generateOrderNumber(string $locationId): string
    {
        // Get the latest order to determine the next sequence
        // Use INTEGER for PostgreSQL compatibility (UNSIGNED is MySQL-specific)
        $latestOrder = Order::where('order_number', 'like', 'ORD-%')
            ->orderByRaw('CAST(SUBSTRING(order_number, 5) AS INTEGER) DESC')
            ->first();
        
        if ($latestOrder && preg_match('/ORD-(\d+)/', $latestOrder->order_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('ORD-%04d', $nextNumber);
    }
}
