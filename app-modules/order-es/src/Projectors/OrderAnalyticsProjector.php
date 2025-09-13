<?php

declare(strict_types=1);

namespace Colame\OrderEs\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\OrderEs\Events\OrderEvents\OrderConverted;
use Colame\OrderEs\Events\OrderEvents\OrderCompleted;
use Colame\OrderEs\Events\OrderEvents\OrderCancelled;
use Colame\OrderEs\Events\OrderEvents\PaymentProcessed;
use Colame\OrderEs\Models\OrderAnalytics;
use Carbon\Carbon;

class OrderAnalyticsProjector extends Projector
{
    /**
     * Handle order converted - start tracking
     */
    public function onOrderConverted(OrderConverted $event): void
    {
        OrderAnalytics::create([
            'order_id' => $event->orderId,
            'location_id' => $event->locationId,
            'order_date' => Carbon::parse($event->placedAt)->toDateString(),
            'hour_of_day' => Carbon::parse($event->placedAt)->hour,
            'day_of_week' => Carbon::parse($event->placedAt)->dayOfWeek,
            'order_type' => $event->type,
            'total_amount' => $event->total,
            'item_count' => $event->itemCount ?? 0,
            'customer_id' => $event->userId,
            'staff_id' => $event->staffId,
            'table_number' => $event->tableNumber,
            'status' => 'placed',
        ]);
    }

    /**
     * Handle order completed
     */
    public function onOrderCompleted(OrderCompleted $event): void
    {
        $analytics = OrderAnalytics::where('order_id', $event->orderId)->first();
        
        if ($analytics) {
            $orderCreatedAt = Carbon::parse($event->metadata['created_at'] ?? now());
            $completedAt = Carbon::parse($event->completedAt);
            
            $analytics->update([
                'status' => 'completed',
                'preparation_time' => $completedAt->diffInMinutes($orderCreatedAt),
                'completed_at' => $completedAt,
            ]);
        }
    }

    /**
     * Handle order cancelled
     */
    public function onOrderCancelled(OrderCancelled $event): void
    {
        $analytics = OrderAnalytics::where('order_id', $event->orderId)->first();
        
        if ($analytics) {
            $analytics->update([
                'status' => 'cancelled',
                'cancellation_reason' => $event->reason,
                'cancelled_at' => now(),
            ]);
        }
    }

    /**
     * Handle payment processed
     */
    public function onPaymentProcessed(PaymentProcessed $event): void
    {
        $analytics = OrderAnalytics::where('order_id', $event->orderId)->first();
        
        if ($analytics) {
            $analytics->update([
                'payment_method' => $event->paymentMethod,
                'tip_amount' => $event->tipAmount ?? 0,
                'discount_amount' => $event->discountAmount ?? 0,
            ]);
        }
    }
}