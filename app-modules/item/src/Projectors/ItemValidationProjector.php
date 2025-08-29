<?php

namespace Colame\Item\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Events\ItemsAddedToOrder;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ItemValidationProjector extends Projector
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
        private ?MenuRepositoryInterface $menuRepository = null
    ) {}

    public function onItemsAddedToOrder(ItemsAddedToOrder $event): void
    {
        try {
            $validatedItems = [];
            $errors = [];
            $subtotal = 0;

            foreach ($event->items as $orderItem) {
                $item = $this->itemRepository->find($orderItem['item_id']);
                
                if (!$item) {
                    $errors[] = [
                        'item_id' => $orderItem['item_id'],
                        'error' => 'Item not found',
                    ];
                    continue;
                }

                // Check if item is available
                if (!$item->isAvailable) {
                    $errors[] = [
                        'item_id' => $orderItem['item_id'],
                        'error' => 'Item not available',
                    ];
                    continue;
                }

                // Check stock if applicable
                if ($item->trackStock && $item->currentStock < $orderItem['quantity']) {
                    $errors[] = [
                        'item_id' => $orderItem['item_id'],
                        'error' => 'Insufficient stock',
                        'available' => $item->currentStock,
                    ];
                    continue;
                }

                // Calculate price with modifiers
                $itemPrice = $item->price;
                $modifierPrice = 0;
                
                if (!empty($orderItem['modifiers'])) {
                    foreach ($orderItem['modifiers'] as $modifier) {
                        $modifierPrice += $modifier['price'] ?? 0;
                    }
                }

                $totalItemPrice = ($itemPrice + $modifierPrice) * $orderItem['quantity'];
                $subtotal += $totalItemPrice;

                $validatedItems[] = [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->categoryName,
                    'quantity' => $orderItem['quantity'],
                    'base_price' => $itemPrice,
                    'modifier_price' => $modifierPrice,
                    'price' => $totalItemPrice,
                    'modifiers' => $orderItem['modifiers'] ?? [],
                    'notes' => $orderItem['notes'] ?? null,
                ];
            }

            if (!empty($errors)) {
                // Emit validation failed event
                event(new \Colame\Item\Events\ItemValidationFailed(
                    orderId: $event->aggregateRootUuid,
                    errors: $errors
                ));
                return;
            }

            // Emit items validated event
            event(new \Colame\Order\Events\ItemsValidated(
                aggregateRootUuid: $event->aggregateRootUuid,
                validatedItems: $validatedItems,
                subtotal: $subtotal,
                currency: 'CLP'
            ));

            // Reserve stock if needed
            foreach ($validatedItems as $item) {
                $this->itemRepository->reserveStock(
                    $item['item_id'],
                    $item['quantity'],
                    $event->aggregateRootUuid
                );
            }

        } catch (\Exception $e) {
            Log::error('Item validation failed', [
                'order_id' => $event->aggregateRootUuid,
                'error' => $e->getMessage(),
            ]);

            event(new \Colame\Item\Events\ItemValidationFailed(
                orderId: $event->aggregateRootUuid,
                errors: [['error' => 'Validation failed: ' . $e->getMessage()]]
            ));
        }
    }
}