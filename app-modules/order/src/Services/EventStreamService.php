<?php

declare(strict_types=1);

namespace Colame\Order\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Spatie\SchemalessAttributes\SchemalessAttributes;
use Colame\Order\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing order event streams and time travel functionality
 */
class EventStreamService
{
    /**
     * Get all events for a specific order
     */
    public function getOrderEventStream(string $orderUuid): Collection
    {
        return EloquentStoredEvent::query()
            ->where('aggregate_uuid', $orderUuid)
            ->orderBy('aggregate_version', 'desc')  // Latest events first
            ->get()
            ->map(function ($storedEvent) {
                return $this->formatStoredEvent($storedEvent);
            });
    }
    
    /**
     * Get order state at a specific timestamp
     */
    public function getOrderStateAtTimestamp(string $orderUuid, Carbon $timestamp): array
    {
        // Get all events up to the specified timestamp
        $events = EloquentStoredEvent::query()
            ->where('aggregate_uuid', $orderUuid)
            ->where('created_at', '<=', $timestamp)
            ->orderBy('aggregate_version')
            ->get();
        
        if ($events->isEmpty()) {
            return [
                'order' => null,
                'timestamp' => $timestamp->toIso8601String(),
                'eventCount' => 0,
            ];
        }
        
        // Get the order projection with relationships at that timestamp
        // Note: This is a simplified version - in production you'd want to replay projections too
        $order = Order::with(['items'])
            ->where('uuid', $orderUuid)
            ->first();
        
        if ($order) {
            // Transform items to the format expected by the frontend
            $items = $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'itemId' => $item->item_id,
                    'name' => $item->item_name ?: $item->base_item_name ?: 'Unknown Item',
                    'quantity' => $item->quantity,
                    'unitPrice' => $item->unit_price / 100, // Convert from cents to dollars
                    'basePrice' => $item->base_price / 100,
                    'modifiersTotal' => $item->modifiers_total / 100,
                    'subtotal' => $item->total_price / 100,
                    'notes' => $item->notes,
                    'modifiers' => $item->modifiers ?? [],
                ];
            })->toArray();
            
            return [
                'uuid' => $order->uuid,
                'orderNumber' => $order->order_number,
                'status' => $order->status,
                'customerName' => $order->customer_name,
                'customerPhone' => $order->customer_phone,
                'customerEmail' => $order->customer_email,
                'locationId' => $order->location_id,
                'locationName' => null, // Location name would come from business module via interface
                'items' => $items,
                'promotionId' => $order->promotion_id,
                'promotionAmount' => ($order->promotion_amount ?? 0) / 100,
                'tipAmount' => ($order->tip_amount ?? 0) / 100,
                'subtotal' => ($order->subtotal ?? 0) / 100,
                'total' => ($order->total_amount ?? 0) / 100,
                'notes' => $order->notes,
                'createdAt' => $order->created_at->toIso8601String(),
                'updatedAt' => $order->updated_at->toIso8601String(),
                'confirmedAt' => $order->confirmed_at?->toIso8601String(),
                'completedAt' => $order->completed_at?->toIso8601String(),
                'cancelledAt' => $order->cancelled_at?->toIso8601String(),
            ];
        }
        
        return null;
    }
    
    /**
     * Get event statistics for an order
     */
    public function getEventStatistics(string $orderUuid): array
    {
        $events = EloquentStoredEvent::query()
            ->where('aggregate_uuid', $orderUuid)
            ->get();
        
        $eventTypes = $events->groupBy('event_class')
            ->map(fn($group) => $group->count())
            ->toArray();
        
        $userActivity = [];
        foreach ($events as $event) {
            // Handle meta_data - it's a SchemalessAttributes object
            $metadata = $event->meta_data instanceof SchemalessAttributes
                ? $event->meta_data->toArray()
                : (is_array($event->meta_data) ? $event->meta_data : []);
            $userId = $metadata['user_id'] ?? 'system';
            $userActivity[$userId] = ($userActivity[$userId] ?? 0) + 1;
        }
        
        $firstEvent = $events->first();
        $lastEvent = $events->last();
        
        return [
            'totalEvents' => $events->count(),
            'eventTypes' => $eventTypes,
            'userActivity' => $userActivity,
            'firstEventAt' => $firstEvent ? Carbon::parse($firstEvent->created_at)->toIso8601String() : null,
            'lastEventAt' => $lastEvent ? Carbon::parse($lastEvent->created_at)->toIso8601String() : null,
            'duration' => $firstEvent && $lastEvent 
                ? Carbon::parse($lastEvent->created_at)->diffForHumans(Carbon::parse($firstEvent->created_at), true)
                : null,
        ];
    }
    
    /**
     * Replay events between two timestamps
     */
    public function replayEventsBetween(string $orderUuid, Carbon $from, Carbon $to): Collection
    {
        return EloquentStoredEvent::query()
            ->where('aggregate_uuid', $orderUuid)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('aggregate_version')
            ->get()
            ->map(function ($storedEvent) {
                return $this->formatStoredEvent($storedEvent);
            });
    }
    
    /**
     * Get the latest snapshot for an order (if any)
     */
    public function getLatestSnapshot(string $orderUuid): ?array
    {
        $snapshot = DB::table('snapshots')
            ->where('aggregate_uuid', $orderUuid)
            ->orderBy('aggregate_version', 'desc')
            ->first();
        
        if (!$snapshot) {
            return null;
        }
        
        // Handle state - it might be a JSON string from DB query
        $state = is_string($snapshot->state) 
            ? json_decode($snapshot->state, true) 
            : $snapshot->state;
        
        return [
            'version' => $snapshot->aggregate_version,
            'state' => $state,
            'createdAt' => Carbon::parse($snapshot->created_at),
        ];
    }
    
    /**
     * Format stored event for frontend consumption
     */
    private function formatStoredEvent(EloquentStoredEvent $storedEvent): array
    {
        $eventClass = $storedEvent->event_class;
        $eventName = class_basename($eventClass);
        
        // Handle event_properties - already cast to array by Eloquent
        $eventProperties = $storedEvent->event_properties ?? [];
            
        // Handle meta_data - it's a SchemalessAttributes object
        $metadata = $storedEvent->meta_data instanceof SchemalessAttributes
            ? $storedEvent->meta_data->toArray()
            : (is_array($storedEvent->meta_data) ? $storedEvent->meta_data : []);
        
        // Extract user information from metadata
        $userId = $metadata['user_id'] ?? null;
        $userName = $metadata['user_name'] ?? 'System';
        
        // Format event description based on type
        $description = $this->getEventDescription($eventName, $eventProperties);
        
        return [
            'id' => $storedEvent->id,
            'type' => $eventName,
            'eventClass' => $eventClass,
            'version' => $storedEvent->aggregate_version,
            'properties' => $eventProperties,
            'metadata' => $metadata,
            'userId' => $userId,
            'userName' => $userName,
            'description' => $description,
            'icon' => $this->getEventIcon($eventName),
            'color' => $this->getEventColor($eventName),
            'createdAt' => Carbon::parse($storedEvent->created_at)->toIso8601String(),
            'timestamp' => Carbon::parse($storedEvent->created_at)->format('g:i:s A'),
            'relativeTime' => Carbon::parse($storedEvent->created_at)->diffForHumans(),
        ];
    }
    
    /**
     * Get human-readable description for an event
     */
    private function getEventDescription(string $eventName, array $properties): string
    {
        $descriptions = [
            'OrderStarted' => 'Order was created',
            'ItemsAddedToOrder' => sprintf('Added %d items to order', count($properties['items'] ?? [])),
            'ItemsValidated' => 'Items were validated',
            'ItemsModified' => 'Order items were modified',
            'PromotionsCalculated' => 'Promotions were calculated',
            'PromotionApplied' => 'Promotion was applied',
            'PromotionRemoved' => 'Promotion was removed',
            'PriceCalculated' => sprintf('Price calculated: %s', $this->formatMoney($properties['total'] ?? 0)),
            'TipAdded' => sprintf('Tip added: %s', $this->formatMoney($properties['tipAmount'] ?? 0)),
            'PaymentMethodSet' => sprintf('Payment method set to %s', $properties['paymentMethod'] ?? 'unknown'),
            'OrderConfirmed' => 'Order was confirmed',
            'OrderCancelled' => sprintf('Order cancelled: %s', $properties['reason'] ?? 'No reason'),
            'OrderStatusTransitioned' => sprintf('Status changed to %s', $properties['newStatus'] ?? 'unknown'),
            'PaymentProcessed' => sprintf('Payment processed: %s', $this->formatMoney($properties['amount'] ?? 0)),
            'PaymentFailed' => sprintf('Payment failed: %s', $properties['failureReason'] ?? 'Unknown error'),
            'CustomerInfoUpdated' => 'Customer information updated',
            'OrderItemsUpdated' => 'Order items updated',
            'ItemModifiersChanged' => sprintf('Modifiers changed for %s', $properties['itemName'] ?? 'item'),
            'PriceAdjusted' => sprintf('Price adjusted: %s', $properties['reason'] ?? 'No reason'),
        ];
        
        return $descriptions[$eventName] ?? $eventName;
    }
    
    /**
     * Get icon for event type
     */
    private function getEventIcon(string $eventName): string
    {
        $icons = [
            'OrderStarted' => 'play-circle',
            'ItemsAddedToOrder' => 'shopping-cart',
            'ItemsValidated' => 'check-circle',
            'ItemsModified' => 'edit',
            'PromotionsCalculated' => 'percent',
            'PromotionApplied' => 'tag',
            'PromotionRemoved' => 'tag-x',
            'PriceCalculated' => 'calculator',
            'TipAdded' => 'dollar-sign',
            'PaymentMethodSet' => 'credit-card',
            'OrderConfirmed' => 'check-circle-2',
            'OrderCancelled' => 'x-circle',
            'OrderStatusTransitioned' => 'arrow-right-circle',
            'PaymentProcessed' => 'check-square',
            'PaymentFailed' => 'alert-triangle',
            'CustomerInfoUpdated' => 'user',
            'OrderItemsUpdated' => 'package',
            'ItemModifiersChanged' => 'sliders',
            'PriceAdjusted' => 'trending-up',
        ];
        
        return $icons[$eventName] ?? 'circle';
    }
    
    /**
     * Get color for event type
     */
    private function getEventColor(string $eventName): string
    {
        $colors = [
            'OrderStarted' => 'blue',
            'ItemsAddedToOrder' => 'green',
            'ItemsValidated' => 'green',
            'ItemsModified' => 'yellow',
            'PromotionsCalculated' => 'purple',
            'PromotionApplied' => 'purple',
            'PromotionRemoved' => 'orange',
            'PriceCalculated' => 'blue',
            'TipAdded' => 'green',
            'PaymentMethodSet' => 'blue',
            'OrderConfirmed' => 'green',
            'OrderCancelled' => 'red',
            'OrderStatusTransitioned' => 'blue',
            'PaymentProcessed' => 'green',
            'PaymentFailed' => 'red',
            'CustomerInfoUpdated' => 'blue',
            'OrderItemsUpdated' => 'yellow',
            'ItemModifiersChanged' => 'yellow',
            'PriceAdjusted' => 'orange',
        ];
        
        return $colors[$eventName] ?? 'gray';
    }
    
    /**
     * Format money value
     */
    private function formatMoney(int $amount): string
    {
        return '$' . number_format($amount / 100, 2);
    }
}