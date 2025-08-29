<?php

namespace Colame\Order\ProcessManagers;

use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;
use Colame\Order\Events\OrderStarted;
use Colame\Order\Events\ItemsValidated;
use Colame\Order\Events\PromotionsCalculated;
use Colame\Order\Events\PriceCalculated;
use Colame\Order\Events\OrderConfirmed;
use Colame\Item\Events\ItemValidationFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TakeOrderProcessManager extends Reactor
{
    private const CACHE_PREFIX = 'order_process:';
    private const TIMEOUT_SECONDS = 30;

    /**
     * Start a new order process and return a process ID
     */
    public function startProcess(array $data): string
    {
        $processId = Str::uuid()->toString();
        
        // Initialize process state
        $processState = [
            'process_id' => $processId,
            'order_uuid' => $data['order_uuid'] ?? null,
            'status' => 'started',
            'steps_completed' => [],
            'current_step' => 'order_started',
            'data' => $data,
            'errors' => [],
            'result' => null,
            'started_at' => now(),
        ];

        Cache::put(
            self::CACHE_PREFIX . $processId,
            $processState,
            now()->addSeconds(self::TIMEOUT_SECONDS)
        );

        return $processId;
    }

    /**
     * Wait for process completion with timeout
     */
    public function waitForCompletion(string $processId, int $timeoutSeconds = 10): array
    {
        $startTime = now();
        
        while (now()->diffInSeconds($startTime) < $timeoutSeconds) {
            $state = $this->getProcessState($processId);
            
            if (!$state) {
                return [
                    'success' => false,
                    'error' => 'Process not found or expired',
                ];
            }

            if ($state['status'] === 'completed') {
                return [
                    'success' => true,
                    'data' => $state['result'],
                ];
            }

            if ($state['status'] === 'failed') {
                return [
                    'success' => false,
                    'error' => $state['errors'][0] ?? 'Process failed',
                    'errors' => $state['errors'],
                ];
            }

            // Wait 100ms before checking again
            usleep(100000);
        }

        return [
            'success' => false,
            'error' => 'Process timeout',
            'last_step' => $state['current_step'] ?? 'unknown',
        ];
    }

    /**
     * Get current state of an order for polling
     */
    public function getOrderState(string $processId): ?array
    {
        $state = $this->getProcessState($processId);
        
        if (!$state) {
            return null;
        }

        return [
            'status' => $state['status'],
            'current_step' => $state['current_step'],
            'steps_completed' => $state['steps_completed'],
            'has_promotions' => !empty($state['promotions'] ?? []),
            'subtotal' => $state['subtotal'] ?? 0,
            'discount' => $state['discount'] ?? 0,
            'total' => $state['total'] ?? 0,
            'errors' => $state['errors'],
        ];
    }

    // Event Handlers

    public function onOrderStarted(OrderStarted $event): void
    {
        $this->updateProcessByOrderUuid($event->aggregateRootUuid, [
            'current_step' => 'validating_items',
            'steps_completed' => array_merge(
                $this->getStepsCompleted($event->aggregateRootUuid),
                ['order_started']
            ),
        ]);
    }

    public function onItemsValidated(ItemsValidated $event): void
    {
        $this->updateProcessByOrderUuid($event->aggregateRootUuid, [
            'current_step' => 'calculating_promotions',
            'steps_completed' => array_merge(
                $this->getStepsCompleted($event->aggregateRootUuid),
                ['items_validated']
            ),
            'validated_items' => $event->validatedItems,
            'subtotal' => $event->subtotal,
        ]);
    }

    public function onItemValidationFailed(ItemValidationFailed $event): void
    {
        $this->updateProcessByOrderUuid($event->orderId, [
            'status' => 'failed',
            'current_step' => 'items_validation_failed',
            'errors' => $event->errors,
        ]);
    }

    public function onPromotionsCalculated(PromotionsCalculated $event): void
    {
        $state = $this->getProcessStateByOrderUuid($event->aggregateRootUuid);
        
        if (!$state) {
            return;
        }

        // Calculate final price
        $subtotal = $state['subtotal'] ?? 0;
        $discount = $event->totalDiscount;
        $taxRate = 0.19; // 19% Chilean IVA
        $taxableAmount = $subtotal - $discount;
        $tax = (int) ($taxableAmount * $taxRate);
        $total = $taxableAmount + $tax;

        $this->updateProcessByOrderUuid($event->aggregateRootUuid, [
            'current_step' => 'awaiting_confirmation',
            'steps_completed' => array_merge(
                $state['steps_completed'] ?? [],
                ['promotions_calculated']
            ),
            'promotions' => [
                'available' => $event->availablePromotions,
                'auto_applied' => $event->autoApplied,
            ],
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ]);

        // If there are available promotions, notify mobile app
        if (!empty($event->availablePromotions)) {
            $this->updateProcessByOrderUuid($event->aggregateRootUuid, [
                'requires_promotion_selection' => true,
            ]);
        }
    }

    public function onPriceCalculated(PriceCalculated $event): void
    {
        $this->updateProcessByOrderUuid($event->aggregateRootUuid, [
            'current_step' => 'price_calculated',
            'steps_completed' => array_merge(
                $this->getStepsCompleted($event->aggregateRootUuid),
                ['price_calculated']
            ),
            'final_price' => [
                'subtotal' => $event->subtotal,
                'discount' => $event->discount,
                'tax' => $event->tax,
                'tip' => $event->tip,
                'total' => $event->total,
            ],
        ]);
    }

    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        $state = $this->getProcessStateByOrderUuid($event->aggregateRootUuid);
        
        if (!$state) {
            return;
        }

        $result = [
            'order_uuid' => $event->aggregateRootUuid,
            'order_number' => $event->orderNumber,
            'status' => 'confirmed',
            'items' => $state['validated_items'] ?? [],
            'promotions' => $state['promotions'] ?? [],
            'pricing' => $state['final_price'] ?? [
                'subtotal' => $state['subtotal'] ?? 0,
                'discount' => $state['discount'] ?? 0,
                'tax' => $state['tax'] ?? 0,
                'tip' => $state['tip'] ?? 0,
                'total' => $state['total'] ?? 0,
            ],
            'confirmed_at' => $event->confirmedAt->toIso8601String(),
        ];

        $this->updateProcessByOrderUuid($event->aggregateRootUuid, [
            'status' => 'completed',
            'current_step' => 'order_confirmed',
            'steps_completed' => array_merge(
                $state['steps_completed'] ?? [],
                ['order_confirmed']
            ),
            'result' => $result,
        ]);
    }

    // Helper Methods

    private function getProcessState(string $processId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $processId);
    }

    private function getProcessStateByOrderUuid(string $orderUuid): ?array
    {
        // Find process by order UUID
        $keys = Cache::get('order_process_index:' . $orderUuid);
        
        if (!$keys) {
            return null;
        }

        return Cache::get($keys);
    }

    private function updateProcessByOrderUuid(string $orderUuid, array $updates): void
    {
        $processKey = Cache::get('order_process_index:' . $orderUuid);
        
        if (!$processKey) {
            Log::warning('Process not found for order', ['order_uuid' => $orderUuid]);
            return;
        }

        $state = Cache::get($processKey);
        
        if (!$state) {
            return;
        }

        $updatedState = array_merge($state, $updates);
        
        Cache::put(
            $processKey,
            $updatedState,
            now()->addSeconds(self::TIMEOUT_SECONDS)
        );
    }

    private function getStepsCompleted(string $orderUuid): array
    {
        $state = $this->getProcessStateByOrderUuid($orderUuid);
        return $state['steps_completed'] ?? [];
    }

    /**
     * Link a process ID to an order UUID for lookup
     */
    public function linkProcessToOrder(string $processId, string $orderUuid): void
    {
        Cache::put(
            'order_process_index:' . $orderUuid,
            self::CACHE_PREFIX . $processId,
            now()->addSeconds(self::TIMEOUT_SECONDS)
        );

        $state = $this->getProcessState($processId);
        if ($state) {
            $state['order_uuid'] = $orderUuid;
            Cache::put(
                self::CACHE_PREFIX . $processId,
                $state,
                now()->addSeconds(self::TIMEOUT_SECONDS)
            );
        }
    }
}