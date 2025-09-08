<?php

namespace Colame\Offer\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Events\ItemsValidated;
use Colame\Order\Events\PromotionsCalculated;
use Colame\Offer\Services\PromotionService;
use Colame\Order\Models\Order;
use Illuminate\Support\Facades\Log;

class PromotionCalculatorProjector extends Projector
{
    public function __construct(
        private PromotionService $promotionService
    ) {}

    public function onItemsValidated(ItemsValidated $event): void
    {
        try {
            $order = Order::find($event->aggregateRootUuid);
            
            if (!$order) {
                Log::warning('Order not found for promotion calculation', [
                    'order_uuid' => $event->aggregateRootUuid
                ]);
                return;
            }

            // Get all applicable promotions
            $applicablePromotions = $this->promotionService->getApplicablePromotions(
                locationId: $order->location_id,
                items: $event->validatedItems,
                subtotal: $event->subtotal,
                customerId: $order->customer_id ?? null
            );

            // Separate auto-apply and manual promotions
            $autoApply = [];
            $availablePromotions = [];
            $totalDiscount = 0;

            foreach ($applicablePromotions as $promotion) {
                $promotionData = [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'description' => $promotion->description,
                    'type' => $promotion->type,
                    'discount_amount' => $this->calculateDiscountAmount(
                        $promotion,
                        $event->subtotal,
                        $event->validatedItems
                    ),
                ];

                if ($promotion->auto_apply) {
                    $autoApply[] = $promotionData;
                    $totalDiscount += $promotionData['discount_amount'];
                } else {
                    $availablePromotions[] = $promotionData;
                }
            }

            // Check for combo deals
            $combos = $this->promotionService->checkCombos($event->validatedItems);
            foreach ($combos as $combo) {
                $comboData = [
                    'id' => $combo->id,
                    'name' => $combo->name,
                    'type' => 'combo',
                    'discount_amount' => $combo->discount_amount,
                    'items_included' => $combo->items,
                ];

                if ($combo->auto_apply) {
                    $autoApply[] = $comboData;
                    $totalDiscount += $combo->discount_amount;
                } else {
                    $availablePromotions[] = $comboData;
                }
            }

            // Emit promotions calculated event
            event(new PromotionsCalculated(
                aggregateRootUuid: $event->aggregateRootUuid,
                availablePromotions: $availablePromotions,
                autoApplied: $autoApply,
                totalDiscount: $totalDiscount
            ));

        } catch (\Exception $e) {
            Log::error('Promotion calculation failed', [
                'order_id' => $event->aggregateRootUuid,
                'error' => $e->getMessage(),
            ]);

            // Still emit event with no promotions so order can continue
            event(new PromotionsCalculated(
                aggregateRootUuid: $event->aggregateRootUuid,
                availablePromotions: [],
                autoApplied: [],
                totalDiscount: 0
            ));
        }
    }

    private function calculateDiscountAmount($promotion, int $subtotal, array $items): int
    {
        switch ($promotion->discount_type) {
            case 'percentage':
                return (int) ($subtotal * ($promotion->discount_value / 100));
            
            case 'fixed':
                return min($promotion->discount_value, $subtotal);
            
            case 'item_discount':
                // Calculate discount based on specific items
                $itemDiscount = 0;
                foreach ($items as $item) {
                    if (in_array($item['item_id'], $promotion->applicable_items ?? [])) {
                        $itemDiscount += $item['price'] * ($promotion->discount_value / 100);
                    }
                }
                return (int) $itemDiscount;
            
            case 'bogo':
                // Buy one get one logic
                $eligibleItems = array_filter($items, fn($item) => 
                    in_array($item['item_id'], $promotion->applicable_items ?? [])
                );
                
                if (count($eligibleItems) >= 2) {
                    // Get the cheapest item price as discount
                    $prices = array_column($eligibleItems, 'price');
                    sort($prices);
                    return (int) ($prices[0] ?? 0);
                }
                return 0;
            
            default:
                return 0;
        }
    }
}