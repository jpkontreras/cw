<?php

declare(strict_types=1);

namespace Colame\OrderEs\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\OrderEs\Events\SessionConverted;
use Colame\OrderEs\Events\OrderEvents\ItemStatusUpdated;
use Colame\OrderEs\Events\OrderEvents\KitchenStatusUpdated;
use Colame\OrderEs\Events\ItemAddedToCart;
use Colame\OrderEs\Events\ItemRemovedFromCart;
use Colame\OrderEs\Events\CartItemModified;
use Colame\OrderEs\Models\OrderItem;
use Colame\OrderEs\Models\OrderSession;
use Illuminate\Support\Str;

class OrderItemProjector extends Projector
{
    /**
     * Track cart items during session
     */
    private array $sessionCarts = [];

    /**
     * Handle cart item added to session
     */
    public function onItemAddedToCart(ItemAddedToCart $event): void
    {
        // Store cart items in memory until order is converted
        if (!isset($this->sessionCarts[$event->sessionId])) {
            $this->sessionCarts[$event->sessionId] = [];
        }

        $modifiersTotal = $this->calculateModifiersTotal($event->modifiers ?? []);
        $totalPrice = ($event->unitPrice + $modifiersTotal) * $event->quantity;
        
        $this->sessionCarts[$event->sessionId][] = [
            'item_id' => $event->itemId,
            'menu_section_id' => null,
            'menu_item_id' => null,
            'item_name' => $event->itemName,
            'base_item_name' => $event->itemName,
            'quantity' => $event->quantity,
            'base_price' => $event->basePrice,
            'unit_price' => $event->unitPrice,
            'modifiers' => $event->modifiers ?? [],
            'modifier_count' => count($event->modifiers ?? []),
            'modifiers_total' => $modifiersTotal,
            'total_price' => $totalPrice,
            'notes' => $event->notes,
            'special_instructions' => null,
            'course' => 'main',
            'status' => 'pending',
            'kitchen_status' => 'pending',
            'metadata' => [],
        ];
    }

    /**
     * Handle cart item removed from session
     */
    public function onItemRemovedFromCart(ItemRemovedFromCart $event): void
    {
        if (isset($this->sessionCarts[$event->sessionId])) {
            // Remove item by index
            unset($this->sessionCarts[$event->sessionId][$event->itemIndex]);
            // Re-index array
            $this->sessionCarts[$event->sessionId] = array_values($this->sessionCarts[$event->sessionId]);
        }
    }

    /**
     * Handle cart item modified in session
     */
    public function onCartItemModified(CartItemModified $event): void
    {
        if (isset($this->sessionCarts[$event->sessionId][$event->itemIndex])) {
            $item = &$this->sessionCarts[$event->sessionId][$event->itemIndex];
            
            if (isset($event->updates['quantity'])) {
                $item['quantity'] = $event->updates['quantity'];
            }
            
            if (isset($event->updates['modifiers'])) {
                $item['modifiers'] = $event->updates['modifiers'];
                $item['modifier_count'] = count($event->updates['modifiers']);
                $item['modifiers_total'] = $this->calculateModifiersTotal($event->updates['modifiers']);
            }
            
            if (isset($event->updates['notes'])) {
                $item['notes'] = $event->updates['notes'];
            }
            
            if (isset($event->updates['specialInstructions'])) {
                $item['special_instructions'] = $event->updates['specialInstructions'];
            }
            
            // Recalculate total
            $item['total_price'] = ($item['unit_price'] + $item['modifiers_total']) * $item['quantity'];
        }
    }

    /**
     * Handle order conversion - create actual order items
     */
    public function onSessionConverted(SessionConverted $event): void
    {
        // Get session ID from the event
        $sessionId = $event->sessionId;
        
        // Get cart items from session
        if (!isset($this->sessionCarts[$sessionId])) {
            return;
        }

        $cartItems = $this->sessionCarts[$sessionId];
        
        // Create order items
        foreach ($cartItems as $index => $itemData) {
            OrderItem::create([
                'id' => Str::uuid()->toString(),
                'order_id' => $event->orderId,
                'item_id' => $itemData['item_id'],
                'menu_section_id' => $itemData['menu_section_id'],
                'menu_item_id' => $itemData['menu_item_id'],
                'item_name' => $itemData['item_name'],
                'base_item_name' => $itemData['base_item_name'],
                'quantity' => $itemData['quantity'],
                'base_price' => $itemData['base_price'],
                'unit_price' => $itemData['unit_price'],
                'modifiers' => $itemData['modifiers'],
                'modifier_count' => $itemData['modifier_count'],
                'modifiers_total' => $itemData['modifiers_total'],
                'total_price' => $itemData['total_price'],
                'notes' => $itemData['notes'],
                'special_instructions' => $itemData['special_instructions'],
                'course' => $itemData['course'],
                'status' => 'pending',
                'kitchen_status' => 'pending',
                'metadata' => $itemData['metadata'],
                'modifier_history' => [],
            ]);
        }
        
        // Clean up session cart
        unset($this->sessionCarts[$sessionId]);
    }

    /**
     * Handle item status update
     */
    public function onItemStatusUpdated(ItemStatusUpdated $event): void
    {
        OrderItem::where('id', $event->itemId)
            ->update([
                'status' => $event->newStatus,
                'updated_at' => now(),
            ]);
    }

    /**
     * Handle kitchen status update
     */
    public function onKitchenStatusUpdated(KitchenStatusUpdated $event): void
    {
        $updates = [
            'kitchen_status' => $event->newStatus,
            'updated_at' => now(),
        ];

        // Add timestamps for specific statuses
        if ($event->newStatus === 'prepared') {
            $updates['prepared_at'] = now();
        } elseif ($event->newStatus === 'served') {
            $updates['served_at'] = now();
        }

        OrderItem::where('id', $event->itemId)
            ->update($updates);
    }

    /**
     * Calculate total price of modifiers
     */
    private function calculateModifiersTotal(array $modifiers): int
    {
        return array_sum(array_map(fn($mod) => $mod['price'] ?? 0, $modifiers));
    }
}